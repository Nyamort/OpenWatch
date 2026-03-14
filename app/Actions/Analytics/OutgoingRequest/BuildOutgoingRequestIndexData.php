<?php

namespace App\Actions\Analytics\OutgoingRequest;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildOutgoingRequestIndexData
{
    /**
     * Build outgoing request analytics grouped by host.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $rows = DB::table('extraction_outgoing_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                'host',
                DB::raw('COUNT(*) as total'),
                DB::raw('CAST(SUM(CASE WHEN status_code IS NULL THEN 1 ELSE 0 END) AS UNSIGNED) as error_count'),
                DB::raw('CAST(SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) AS UNSIGNED) as count_2xx'),
                DB::raw('CAST(SUM(CASE WHEN status_code >= 300 AND status_code < 400 THEN 1 ELSE 0 END) AS UNSIGNED) as count_3xx'),
                DB::raw('CAST(SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS UNSIGNED) as count_4xx'),
                DB::raw('CAST(SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS UNSIGNED) as count_5xx'),
                DB::raw('CAST(ROUND(AVG(duration), 2) AS DOUBLE) as avg_duration'),
                DB::raw('ROUND((MAX(duration)) , 2) as p95_duration'),
            ])
            ->groupBy('host')
            ->orderBy('total', 'desc')
            ->paginate(50);

        return (new AnalyticsResponseBuilder)
            ->withSummary(['period_label' => $period->label])
            ->withRows($rows->items())
            ->withPagination([
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ])
            ->withConfig(['period' => $period->label])
            ->build();
    }
}
