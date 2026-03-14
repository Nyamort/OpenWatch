<?php

namespace App\Actions\Analytics\User;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildUserIndexData
{
    /**
     * Build user analytics by aggregating across requests, exceptions, and job attempts.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $orgId = $ctx->organization->id;
        $projId = $ctx->project->id;
        $envId = $ctx->environment->id;
        $start = $period->start;
        $end = $period->end;

        // Use UNION ALL to merge user identifiers from requests and exceptions, then GROUP BY.
        // Alias `user` as `user_id` to avoid conflict with MySQL reserved keyword.
        $rows = DB::table(DB::raw('(
            SELECT `user` AS user_id, 1 AS is_request, 0 AS is_exception
            FROM extraction_requests
            WHERE organization_id = ? AND project_id = ? AND environment_id = ?
              AND recorded_at BETWEEN ? AND ?
              AND `user` IS NOT NULL AND `user` != \'\'
            UNION ALL
            SELECT `user`, 0, 1
            FROM extraction_exceptions
            WHERE organization_id = ? AND project_id = ? AND environment_id = ?
              AND recorded_at BETWEEN ? AND ?
              AND `user` IS NOT NULL AND `user` != \'\'
        ) AS combined'))
            ->addBinding([$orgId, $projId, $envId, $start, $end], 'select')
            ->addBinding([$orgId, $projId, $envId, $start, $end], 'select')
            ->selectRaw('user_id, CAST(SUM(is_request) AS UNSIGNED) as request_count, CAST(SUM(is_exception) AS UNSIGNED) as exception_count')
            ->groupBy('user_id')
            ->orderByDesc('request_count')
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
