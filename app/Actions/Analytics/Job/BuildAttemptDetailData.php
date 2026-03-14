<?php

namespace App\Actions\Analytics\Job;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use Illuminate\Support\Facades\DB;

class BuildAttemptDetailData
{
    /**
     * Fetch a single job_attempt with related logs, queries, and exceptions by execution_id.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, string $attemptId): array
    {
        $attempt = DB::table('extraction_job_attempts')
            ->where('attempt_id', $attemptId)
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->first();

        if ($attempt === null) {
            abort(404, 'Job attempt not found.');
        }

        $logs = DB::table('extraction_logs')
            ->where('execution_id', $attempt->execution_id)
            ->where('organization_id', $ctx->organization->id)
            ->orderBy('recorded_at')
            ->get()
            ->toArray();

        $queries = DB::table('extraction_queries')
            ->where('execution_id', $attempt->execution_id)
            ->where('organization_id', $ctx->organization->id)
            ->orderBy('recorded_at')
            ->get()
            ->toArray();

        $exceptions = DB::table('extraction_exceptions')
            ->where('execution_id', $attempt->execution_id)
            ->where('organization_id', $ctx->organization->id)
            ->orderBy('recorded_at')
            ->get()
            ->toArray();

        return (new AnalyticsResponseBuilder)
            ->withSummary((array) $attempt)
            ->withRows([
                'logs' => $logs,
                'queries' => $queries,
                'exceptions' => $exceptions,
            ])
            ->build();
    }
}
