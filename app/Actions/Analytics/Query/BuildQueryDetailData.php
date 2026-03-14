<?php

namespace App\Actions\Analytics\Query;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildQueryDetailData
{
    /**
     * Build all occurrences for a given sql_hash, ordered newest-first, paginated.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sqlHash): array
    {
        $rows = DB::table('extraction_queries')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('sql_hash', $sqlHash)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->orderBy('recorded_at', 'desc')
            ->paginate(50);

        $summary = DB::table('extraction_queries')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('sql_hash', $sqlHash)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                DB::raw('LEFT(MIN(sql_normalized), 200) as sql_preview'),
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND((AVG(duration) / 1000.0) , 3) as avg_duration_ms'),
                DB::raw('ROUND((MAX(duration) / 1000.0) , 3) as p95_duration_ms'),
                DB::raw('ROUND((MAX(duration) / 1000.0) , 3) as max_duration_ms'),
            ])
            ->first();

        return (new AnalyticsResponseBuilder)
            ->withSummary([
                'sql_hash' => $sqlHash,
                'sql_preview' => $summary?->sql_preview,
                'total' => $summary?->total ?? 0,
                'avg_duration_ms' => $summary?->avg_duration_ms ?? 0,
                'p95_duration_ms' => $summary?->p95_duration_ms ?? 0,
                'max_duration_ms' => $summary?->max_duration_ms ?? 0,
                'period_label' => $period->label,
            ])
            ->withRows($rows->items())
            ->withPagination([
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ])
            ->build();
    }
}
