<?php

namespace App\Actions\Analytics\Request;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildRequestRouteData
{
    /**
     * Build aggregated analytics for a single route + bucketed chart data.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $routePath,
        string $method,
    ): array {
        $baseQuery = fn () => DB::table('extraction_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('route_path', $routePath)
            ->where('method', strtoupper($method))
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        $summary = $baseQuery()
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('CAST(ROUND(AVG(duration), 2) AS DOUBLE) as avg_duration'),
                DB::raw('MAX(duration) as p95_duration'),
                DB::raw('ROUND((SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0)) , 2) as error_rate'),
            ])
            ->first();

        $series = $baseQuery()
            ->select([
                DB::raw("date_trunc('second', recorded_at) - INTERVAL '1 second' * (EXTRACT(EPOCH FROM recorded_at)::int % {$period->bucketSeconds}) as bucket"),
                DB::raw('COUNT(*) as count'),
                DB::raw('CAST(ROUND(AVG(duration), 2) AS DOUBLE) as avg_duration'),
            ])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->toArray();

        return (new AnalyticsResponseBuilder)
            ->withSummary([
                'route_path' => $routePath,
                'method' => strtoupper($method),
                'total' => $summary?->total ?? 0,
                'avg_duration' => $summary?->avg_duration ?? 0,
                'p95_duration' => $summary?->p95_duration ?? 0,
                'error_rate' => $summary?->error_rate ?? 0,
                'period_label' => $period->label,
            ])
            ->withSeries($series)
            ->withConfig([
                'period' => $period->label,
                'bucket_seconds' => $period->bucketSeconds,
            ])
            ->build();
    }
}
