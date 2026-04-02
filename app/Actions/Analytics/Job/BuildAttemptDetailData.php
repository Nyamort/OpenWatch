<?php

namespace App\Actions\Analytics\Job;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildAttemptDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single job attempt with related logs, queries, and exceptions by attempt_id.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, string $attemptId): array
    {
        $orgId = $ctx->organization->id;
        $escapedId = ClickHouseService::escape($attemptId);

        $attempt = $this->clickhouse->selectOne("
            SELECT *
            FROM extraction_job_attempts
            WHERE attempt_id = {$escapedId}
              AND organization_id = {$orgId}
            LIMIT 1
        ");

        if ($attempt === null) {
            abort(404, 'Job attempt not found.');
        }

        $executionId = ClickHouseService::escape($attempt->attempt_id ?? '');

        $logs = $this->clickhouse->select("
            SELECT *
            FROM extraction_logs
            WHERE execution_id = {$executionId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

        $queries = $this->clickhouse->select("
            SELECT *
            FROM extraction_queries
            WHERE execution_id = {$executionId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

        $exceptions = $this->clickhouse->select("
            SELECT *
            FROM extraction_exceptions
            WHERE execution_id = {$executionId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

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
