<?php

namespace App\Actions\Analytics\Job;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildJobsIndexData
{
    /**
     * Build jobs analytics by grouping job_attempts by name with status counters.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $rows = DB::table('extraction_job_attempts')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                'name',
                'queue',
                'connection',
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw("CAST(SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) AS UNSIGNED) as processed_count"),
                DB::raw("CAST(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS UNSIGNED) as failed_count"),
                DB::raw("CAST(SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) AS UNSIGNED) as released_count"),
                DB::raw('CAST(ROUND(AVG(duration), 2) AS DOUBLE) as avg_duration'),
            ])
            ->groupBy('name', 'queue', 'connection')
            ->orderBy('total_attempts', 'desc')
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
