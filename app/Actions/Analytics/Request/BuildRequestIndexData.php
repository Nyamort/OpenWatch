<?php

namespace App\Actions\Analytics\Request;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildRequestIndexData
{
    /**
     * Build aggregated request analytics grouped by route_path + method.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        ?string $search = null,
        ?string $sort = null,
        ?string $direction = null,
    ): array {
        $query = DB::table('extraction_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                'route_path',
                'method',
                DB::raw('COUNT(*) as total'),
                DB::raw('CAST(ROUND(AVG(duration), 2) AS DOUBLE) as avg_duration'),
                DB::raw('MAX(duration) as p95_duration'),
                DB::raw('CAST(ROUND(SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS DOUBLE) as error_rate'),
            ])
            ->groupBy('route_path', 'method');

        if ($search !== null && $search !== '') {
            $query->where('route_path', 'like', '%'.$search.'%');
        }

        $allowedSorts = ['total', 'avg_duration', 'p95_duration', 'error_rate'];
        $sortColumn = in_array($sort, $allowedSorts, true) ? $sort : 'total';
        $sortDir = $direction === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortColumn, $sortDir);

        $rows = $query->paginate(50);

        $totalRequests = DB::table('extraction_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->count();

        return (new AnalyticsResponseBuilder)
            ->withSummary([
                'total_requests' => $totalRequests,
                'period_label' => $period->label,
            ])
            ->withRows($rows->items())
            ->withPagination([
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ])
            ->withFiltersApplied([
                'search' => $search,
                'sort' => $sortColumn,
                'direction' => $sortDir,
            ])
            ->withConfig([
                'period' => $period->label,
                'bucket_seconds' => $period->bucketSeconds,
            ])
            ->build();
    }
}
