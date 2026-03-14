<?php

namespace App\Actions\Analytics\Query;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildQueryIndexData
{
    /**
     * Build aggregated query analytics grouped by sql_hash.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $rows = DB::table('extraction_queries')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                'sql_hash',
                DB::raw('LEFT(MIN(sql_normalized), 200) as sql_preview'),
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND((AVG(duration) / 1000.0) , 3) as avg_duration_ms'),
                DB::raw('ROUND((MAX(duration) / 1000.0) , 3) as p95_duration_ms'),
                DB::raw('ROUND((MAX(duration) / 1000.0) , 3) as max_duration_ms'),
                DB::raw('MIN(id) as example_id'),
            ])
            ->groupBy('sql_hash')
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
