<?php

namespace App\Actions\Dashboard;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BuildDashboardData
{
    /**
     * Build the summary metrics for the dashboard.
     * Cached with short TTL (30s for 1h period, 5min for 30d).
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $ttl = match (true) {
            $period->bucketSeconds <= 30 => 30,   // 1h period
            $period->bucketSeconds <= 900 => 60,  // 24h period
            default => 300,                        // 7d/14d/30d
        };

        $cacheKey = "dashboard:{$ctx->organization->id}:{$ctx->project->id}:{$ctx->environment->id}:{$period->label}";

        return Cache::remember($cacheKey, $ttl, function () use ($ctx, $period): array {
            return [
                'requests' => $this->buildRequestMetrics($ctx, $period),
                'exceptions' => $this->buildExceptionMetrics($ctx, $period),
                'jobs' => $this->buildJobMetrics($ctx, $period),
                'users' => $this->buildUserMetrics($ctx, $period),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestMetrics(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $result = DB::table('extraction_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->selectRaw('COUNT(*) as total, CAST(SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS UNSIGNED) as errors, MAX(duration) as max_duration')
            ->first();

        $total = (int) ($result?->total ?? 0);
        $errors = (int) ($result?->errors ?? 0);
        $errorRate = $total > 0 ? round($errors / $total * 100, 1) : 0.0;

        return [
            'total' => $total,
            'error_count' => $errors,
            'error_rate' => $errorRate,
            'p95_duration' => (int) ($result?->max_duration ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildExceptionMetrics(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $result = DB::table('extraction_exceptions')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->selectRaw('COUNT(*) as total, CAST(SUM(CASE WHEN handled = 0 THEN 1 ELSE 0 END) AS UNSIGNED) as unhandled')
            ->first();

        return [
            'total' => (int) ($result?->total ?? 0),
            'unhandled' => (int) ($result?->unhandled ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildJobMetrics(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $result = DB::table('extraction_job_attempts')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->selectRaw("COUNT(*) as total, CAST(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS UNSIGNED) as failed")
            ->first();

        $total = (int) ($result?->total ?? 0);
        $failed = (int) ($result?->failed ?? 0);

        return [
            'total' => $total,
            'failed' => $failed,
            'failure_rate' => $total > 0 ? round($failed / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUserMetrics(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $result = DB::table('extraction_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->whereNotNull('user')
            ->where('user', '!=', '')
            ->selectRaw('COUNT(DISTINCT `user`) as authenticated, COUNT(*) as total_requests')
            ->first();

        return [
            'authenticated' => (int) ($result?->authenticated ?? 0),
        ];
    }
}
