<?php

namespace App\Actions\Analytics\User;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildUserDetailData
{
    /**
     * Build detail view for a given user value, showing their requests, exceptions, and jobs.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $userValue): array
    {
        $requests = DB::table('extraction_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('user', $userValue)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();

        $exceptions = DB::table('extraction_exceptions')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('user', $userValue)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();

        $jobs = DB::table('extraction_job_attempts')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('user', $userValue)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();

        return (new AnalyticsResponseBuilder)
            ->withSummary([
                'user' => $userValue,
                'request_count' => count($requests),
                'exception_count' => count($exceptions),
                'job_count' => count($jobs),
                'period_label' => $period->label,
            ])
            ->withRows([
                'requests' => $requests,
                'exceptions' => $exceptions,
                'jobs' => $jobs,
            ])
            ->build();
    }
}
