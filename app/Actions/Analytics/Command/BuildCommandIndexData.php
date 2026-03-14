<?php

namespace App\Actions\Analytics\Command;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildCommandIndexData
{
    /**
     * Build command analytics grouped by name with status counters.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $rows = DB::table('extraction_commands')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                'name',
                DB::raw('COUNT(*) as total'),
                DB::raw("CAST(SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS UNSIGNED) as success_count"),
                DB::raw("CAST(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS UNSIGNED) as failed_count"),
                DB::raw("CAST(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS UNSIGNED) as pending_count"),
                DB::raw("CAST(ROUND(AVG(CASE WHEN status != 'pending' THEN duration END) , 2) AS DOUBLE) as avg_duration"),
            ])
            ->groupBy('name')
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
