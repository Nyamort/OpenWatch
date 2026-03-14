<?php

namespace App\Actions\Analytics\Exception;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildExceptionDetailData
{
    /**
     * Resolve group_key to representative record (latest) and all occurrences paginated.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $groupKey): array
    {
        $representative = DB::table('extraction_exceptions')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('group_key', $groupKey)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if ($representative === null) {
            abort(404, 'Exception group not found.');
        }

        $occurrences = DB::table('extraction_exceptions')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('group_key', $groupKey)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->orderBy('recorded_at', 'desc')
            ->paginate(50);

        $relatedRequests = DB::table('extraction_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('trace_id', $representative->trace_id)
            ->get(['id', 'route_path', 'method', 'status_code', 'recorded_at'])
            ->toArray();

        return (new AnalyticsResponseBuilder)
            ->withSummary(array_merge((array) $representative, [
                'related_requests' => $relatedRequests,
            ]))
            ->withRows($occurrences->items())
            ->withPagination([
                'current_page' => $occurrences->currentPage(),
                'last_page' => $occurrences->lastPage(),
                'per_page' => $occurrences->perPage(),
                'total' => $occurrences->total(),
            ])
            ->build();
    }
}
