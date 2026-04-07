<?php

namespace App\Actions\Analytics\Command;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\SpanBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildCommandShowData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single command run with its related events.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, string $commandId): array
    {
        $envId = $ctx->environment->id;
        $escapedId = ClickHouseService::escape($commandId);

        $command = $this->clickhouse->selectOne("
            SELECT *
            FROM extraction_commands
            WHERE id = {$escapedId}
              AND environment_id = {$envId}
            LIMIT 1
        ");

        if ($command === null) {
            abort(404, 'Command run not found.');
        }

        $executionId = ClickHouseService::escape($command->id);
        $start = ClickHouseService::escape($command->recorded_at);
        $end = ClickHouseService::escape(
            \Carbon\Carbon::parse($command->recorded_at)
                ->addMicroseconds((int) $command->duration)
                ->format('Y-m-d H:i:s.u')
        );

        $queries = $this->clickhouse->select("
            SELECT *
            FROM extraction_queries
            WHERE execution_id = {$executionId}
              AND environment_id = {$envId}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at
        ")->toArray();

        $mails = $this->clickhouse->select("
            SELECT *
            FROM extraction_mails
            WHERE execution_id = {$executionId}
              AND environment_id = {$envId}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at
        ")->toArray();

        $notifications = $this->clickhouse->select("
            SELECT *
            FROM extraction_notifications
            WHERE execution_id = {$executionId}
              AND environment_id = {$envId}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at
        ")->toArray();

        $cacheEvents = $this->clickhouse->select("
            SELECT *
            FROM extraction_cache_events
            WHERE execution_id = {$executionId}
              AND environment_id = {$envId}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at
        ")->toArray();

        $outgoingRequests = $this->clickhouse->select("
            SELECT *
            FROM extraction_outgoing_requests
            WHERE execution_id = {$executionId}
              AND environment_id = {$envId}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at
        ")->toArray();

        $queuedJobs = $this->clickhouse->select("
            SELECT *
            FROM extraction_queued_jobs
            WHERE execution_id = {$executionId}
              AND environment_id = {$envId}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at
        ")->toArray();

        $summary = array_merge((array) $command, [
            'mail_count' => count($mails),
        ]);

        return (new AnalyticsResponseBuilder)
            ->withSummary($summary)
            ->withRows(['executions' => $this->buildExecutions($command, $queries, $mails, $notifications, $cacheEvents, $outgoingRequests, $queuedJobs)])
            ->build();
    }

    /**
     * @param  array<int, object>  $queries
     * @param  array<int, object>  $mails
     * @param  array<int, object>  $notifications
     * @param  array<int, object>  $cacheEvents
     * @param  array<int, object>  $outgoingRequests
     * @param  array<int, object>  $queuedJobs
     * @return array<int, array<string, mixed>>
     */
    private function buildExecutions(
        object $command,
        array $queries,
        array $mails,
        array $notifications,
        array $cacheEvents,
        array $outgoingRequests,
        array $queuedJobs,
    ): array {
        $totalDurationUs = (int) ($command->duration ?? 0);
        $builder = new SpanBuilder($command->recorded_at, $totalDurationUs);

        $spansByStage = SpanBuilder::groupByStage(
            [$queries, $builder->querySpan(...)],
            [$mails, $builder->mailSpan(...)],
            [$notifications, $builder->notificationSpan(...)],
            [$cacheEvents, $builder->cacheSpan(...)],
            [$outgoingRequests, $builder->outgoingRequestSpan(...)],
            [$queuedJobs, $builder->queuedJobSpan(...)],
        );

        $stages = SpanBuilder::buildStagesFromPhases($spansByStage, [
            ['id' => 'bootstrap', 'name' => 'bootstrap', 'duration' => (int) ($command->bootstrap ?? 0)],
            ['id' => 'action', 'name' => 'action', 'duration' => (int) ($command->action ?? 0), 'description' => $command->class ?? ''],
            ['id' => 'terminating', 'name' => 'terminating', 'duration' => (int) ($command->terminating ?? 0)],
        ]);

        $exitCode = $command->exit_code ?? null;
        $variant = $exitCode === null ? 'warning' : ($exitCode === 0 ? 'success' : 'error');

        return [$builder->buildExecution(
            $command->id,
            'command',
            $command->command ?? $command->name,
            (int) ($exitCode ?? 0),
            $variant,
            $stages,
        )];
    }
}
