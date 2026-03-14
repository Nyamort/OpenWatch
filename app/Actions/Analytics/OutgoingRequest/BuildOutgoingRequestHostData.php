<?php

namespace App\Actions\Analytics\OutgoingRequest;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildOutgoingRequestHostData
{
    /**
     * Build individual request list for a given host, ordered by recorded_at desc.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $host): array
    {
        $rows = DB::table('extraction_outgoing_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('host', $host)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->orderBy('recorded_at', 'desc')
            ->paginate(50);

        return (new AnalyticsResponseBuilder)
            ->withSummary([
                'host' => $host,
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
