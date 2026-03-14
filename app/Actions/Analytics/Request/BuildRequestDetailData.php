<?php

namespace App\Actions\Analytics\Request;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use Illuminate\Support\Facades\DB;

class BuildRequestDetailData
{
    /**
     * Fetch a single extraction_requests row with related data by trace_id.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, int $requestId): array
    {
        $request = DB::table('extraction_requests')
            ->where('id', $requestId)
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->first();

        if ($request === null) {
            abort(404, 'Request not found.');
        }

        $queries = DB::table('extraction_queries')
            ->where('trace_id', $request->trace_id)
            ->where('organization_id', $ctx->organization->id)
            ->orderBy('recorded_at')
            ->get()
            ->toArray();

        $exceptions = DB::table('extraction_exceptions')
            ->where('trace_id', $request->trace_id)
            ->where('organization_id', $ctx->organization->id)
            ->orderBy('recorded_at')
            ->get()
            ->toArray();

        $logs = DB::table('extraction_logs')
            ->where('execution_id', $request->trace_id)
            ->where('organization_id', $ctx->organization->id)
            ->orderBy('recorded_at')
            ->get()
            ->toArray();

        return (new AnalyticsResponseBuilder)
            ->withSummary((array) $request)
            ->withRows([
                'queries' => $queries,
                'exceptions' => $exceptions,
                'logs' => $logs,
            ])
            ->build();
    }
}
