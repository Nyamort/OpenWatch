<?php

namespace App\Actions\Analytics\Job;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildAttemptDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single job attempt with its user details and related events.
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

        $userDetails = $this->fetchUserDetails($orgId, $attempt->user ?? null);
        $summary = array_merge((array) $attempt, [
            'user_name' => $userDetails?->name,
            'user_email' => $userDetails?->username,
        ]);

        return (new AnalyticsResponseBuilder)
            ->withSummary($summary)
            ->withRows([
                'logs' => $logs,
                'queries' => $queries,
                'exceptions' => $exceptions,
            ])
            ->build();
    }

    private function fetchUserDetails(int $orgId, ?string $userId): ?object
    {
        if ($userId === null || $userId === '') {
            return null;
        }

        $escapedUserId = ClickHouseService::escape($userId);

        return $this->clickhouse->selectOne("
            SELECT any(name) AS name, username
            FROM extraction_user_activities
            WHERE organization_id = {$orgId}
              AND user_id = {$escapedUserId}
              AND username != ''
            GROUP BY username
            LIMIT 1
        ");
    }
}
