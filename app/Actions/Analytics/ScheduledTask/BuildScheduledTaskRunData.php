<?php

namespace App\Actions\Analytics\ScheduledTask;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildScheduledTaskRunData
{
    /**
     * Build individual runs for a (name, cron) combo, ordered by recorded_at desc, paginated.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $name,
        string $cron,
    ): array {
        $rows = DB::table('extraction_scheduled_tasks')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('name', $name)
            ->where('cron', $cron)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->orderBy('recorded_at', 'desc')
            ->paginate(50);

        return (new AnalyticsResponseBuilder)
            ->withSummary([
                'name' => $name,
                'cron' => $cron,
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
