<?php

namespace App\Actions\Analytics\Exception;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildExceptionIndexData
{
    /**
     * Build exception analytics grouped by group_key.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        ?string $search = null,
    ): array {
        $query = DB::table('extraction_exceptions')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                'group_key',
                DB::raw('MIN(class) as class'),
                DB::raw('COUNT(*) as total'),
                DB::raw('CAST(SUM(CASE WHEN handled = 1 THEN 1 ELSE 0 END) AS UNSIGNED) as handled_count'),
                DB::raw('CAST(SUM(CASE WHEN handled = 0 THEN 1 ELSE 0 END) AS UNSIGNED) as unhandled_count'),
                DB::raw('MIN(recorded_at) as first_seen'),
                DB::raw('MAX(recorded_at) as last_seen'),
            ])
            ->groupBy('group_key')
            ->orderBy('last_seen', 'desc');

        if ($search !== null && $search !== '') {
            $query->where('class', 'like', '%'.$search.'%');
        }

        $rows = $query->paginate(50);

        return (new AnalyticsResponseBuilder)
            ->withSummary(['period_label' => $period->label])
            ->withRows($rows->items())
            ->withPagination([
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ])
            ->withFiltersApplied(['search' => $search])
            ->withConfig(['period' => $period->label])
            ->build();
    }
}
