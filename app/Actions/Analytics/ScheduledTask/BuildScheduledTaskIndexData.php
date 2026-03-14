<?php

namespace App\Actions\Analytics\ScheduledTask;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildScheduledTaskIndexData
{
    /**
     * Build scheduled task analytics grouped by (name, cron) with status counters.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $rows = DB::table('extraction_scheduled_tasks')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                'name',
                'cron',
                DB::raw('COUNT(*) as total'),
                DB::raw("CAST(SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) AS UNSIGNED) as processed_count"),
                DB::raw("CAST(SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) AS UNSIGNED) as skipped_count"),
                DB::raw("CAST(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS UNSIGNED) as failed_count"),
                DB::raw("CAST(ROUND(AVG(CASE WHEN status != 'skipped' THEN duration END) , 2) AS DOUBLE) as avg_duration"),
            ])
            ->groupBy('name', 'cron')
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
