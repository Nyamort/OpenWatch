<?php

namespace App\Actions\Analytics\Job;

use App\Concerns\FetchesUserDetails;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\SpanBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildAttemptDetailData
{
    use FetchesUserDetails;

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
        $start = ClickHouseService::escape($attempt->recorded_at);
        $end = ClickHouseService::escape(
            \Carbon\Carbon::parse($attempt->recorded_at)
                ->addMicroseconds((int) $attempt->duration)
                ->format('Y-m-d H:i:s.u')
        );

        $queries = $this->clickhouse->select("
            SELECT *
            FROM extraction_queries
            WHERE execution_id = {$executionId}
              AND organization_id = {$orgId}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at
        ")->toArray();

        $exceptions = $this->clickhouse->select("
            SELECT *
            FROM extraction_exceptions
            WHERE execution_id = {$executionId}
              AND organization_id = {$orgId}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at
        ")->toArray();

        $logs = $this->clickhouse->select("
            SELECT *
            FROM extraction_logs
            WHERE execution_id = {$executionId}
              AND organization_id = {$orgId}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at
        ")->toArray();

        $userDetails = $this->fetchUserDetails($orgId, $attempt->user ?? null);
        $summary = array_merge((array) $attempt, [
            'user_name' => $userDetails?->name,
            'user_email' => $userDetails?->username,
        ]);

        return (new AnalyticsResponseBuilder)
            ->withSummary($summary)
            ->withRows(['executions' => $this->buildExecutions($attempt, $queries, $exceptions, $logs)])
            ->build();
    }

    /**
     * @param  array<int, object>  $queries
     * @param  array<int, object>  $exceptions
     * @param  array<int, object>  $logs
     * @return array<int, array<string, mixed>>
     */
    private function buildExecutions(object $attempt, array $queries, array $exceptions, array $logs): array
    {
        $totalDurationUs = (int) ($attempt->duration ?? 0);
        $builder = new SpanBuilder($attempt->recorded_at, $totalDurationUs);

        $spans = SpanBuilder::sortByOffset(array_merge(
            array_map($builder->querySpan(...), $queries),
            array_map($builder->exceptionSpan(...), $exceptions),
            array_map($builder->logSpan(...), $logs),
        ));

        $variant = match ($attempt->status) {
            'processed' => 'success',
            'released' => 'warning',
            default => 'error',
        };

        return [$builder->buildExecution(
            $attempt->attempt_id,
            'job',
            $attempt->name,
            0,
            $variant,
            [SpanBuilder::buildStage('execution', 'execution', '', $totalDurationUs, 0, $spans)],
        )];
    }
}
