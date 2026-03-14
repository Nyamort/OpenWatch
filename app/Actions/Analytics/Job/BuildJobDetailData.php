<?php

namespace App\Actions\Analytics\Job;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use Illuminate\Support\Facades\DB;

class BuildJobDetailData
{
    /**
     * Fetch a single queued_job with all its job_attempts ordered by attempt number.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, int $jobId): array
    {
        $job = DB::table('extraction_queued_jobs')
            ->where('id', $jobId)
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->first();

        if ($job === null) {
            abort(404, 'Job record not found.');
        }

        $attempts = DB::table('extraction_job_attempts')
            ->where('job_id', $job->job_id)
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->orderBy('attempt')
            ->get()
            ->toArray();

        return (new AnalyticsResponseBuilder)
            ->withSummary((array) $job)
            ->withRows($attempts)
            ->build();
    }
}
