<?php

namespace App\Actions\Dashboard;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Illuminate\Support\Facades\Cache;

class BuildDashboardData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

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

        $cacheKey = "dashboard:{$ctx->environment->id}:{$period->label}";

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
        $where = $this->baseWhere($ctx, $period);

        $result = $this->clickhouse->selectOne("
            SELECT
                count() AS total,
                countIf(status_code >= 500) AS errors,
                toFloat64(quantile(0.95)(duration)) AS p95
            FROM extraction_requests {$where}
        ");

        $total = (int) ($result?->total ?? 0);
        $errors = (int) ($result?->errors ?? 0);

        return [
            'total' => $total,
            'error_count' => $errors,
            'error_rate' => $total > 0 ? round($errors / $total * 100, 1) : 0.0,
            'p95_duration' => (float) ($result?->p95 ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildExceptionMetrics(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $where = $this->baseWhere($ctx, $period);

        $result = $this->clickhouse->selectOne("
            SELECT
                count() AS total,
                countIf(handled = 0) AS unhandled
            FROM extraction_exceptions {$where}
        ");

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
        $where = $this->baseWhere($ctx, $period);

        $result = $this->clickhouse->selectOne("
            SELECT
                count() AS total,
                countIf(status = 'failed') AS failed
            FROM extraction_job_attempts {$where}
        ");

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
        $where = $this->baseWhere($ctx, $period);

        $authenticated = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(user)
            FROM extraction_requests {$where} AND user != ''
        ") ?? 0);

        return [
            'authenticated' => $authenticated,
        ];
    }

    private function baseWhere(AnalyticsContext $ctx, PeriodResult $period): string
    {
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);

        return "WHERE environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";
    }
}
