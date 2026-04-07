<?php

namespace App\Actions\Analytics\User;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;

class BuildUserDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build detail view for a given user value, showing their requests, exceptions, and jobs.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $userValue): array
    {
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $user = ClickHouseService::escape($userValue);

        $baseWhere = "WHERE environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}
            AND user = {$user}";

        $requests = $this->clickhouse->select("
            SELECT * FROM extraction_requests {$baseWhere}
            ORDER BY recorded_at DESC LIMIT 50
        ")->all();

        $exceptions = $this->clickhouse->select("
            SELECT * FROM extraction_exceptions {$baseWhere}
            ORDER BY recorded_at DESC LIMIT 50
        ")->all();

        $jobs = $this->clickhouse->select("
            SELECT * FROM extraction_job_attempts {$baseWhere}
            ORDER BY recorded_at DESC LIMIT 50
        ")->all();

        return [
            'summary' => [
                'user' => $userValue,
                'request_count' => count($requests),
                'exception_count' => count($exceptions),
                'job_count' => count($jobs),
                'period_label' => $period->label,
            ],
            'requests' => $requests,
            'exceptions' => $exceptions,
            'jobs' => $jobs,
        ];
    }
}
