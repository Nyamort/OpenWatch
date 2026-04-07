<?php

namespace App\Actions\Analytics\ScheduledTask;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\SpanBuilder;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildScheduledTaskShowData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single scheduled task run with its related events.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, string $runId): array
    {
        $envId = $ctx->environment->id;
        $escapedId = ClickHouseService::escape($runId);

        $task = $this->clickhouse->selectOne("
            SELECT *
            FROM extraction_scheduled_tasks
            WHERE id = {$escapedId}
              AND environment_id = {$envId}
            LIMIT 1
        ");

        if ($task === null) {
            abort(404, 'Scheduled task run not found.');
        }

        $executionId = ClickHouseService::escape($task->id);
        $start = ClickHouseService::escape($task->recorded_at);
        $end = ClickHouseService::escape(
            Carbon::parse($task->recorded_at)
                ->addMicroseconds((int) $task->duration)
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

        $summary = array_merge((array) $task, [
            'mail_count' => count($mails),
        ]);

        return (new AnalyticsResponseBuilder)
            ->withSummary($summary)
            ->withRows(['executions' => $this->buildExecutions($task, $queries, $mails, $notifications, $cacheEvents, $outgoingRequests, $queuedJobs)])
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
        object $task,
        array $queries,
        array $mails,
        array $notifications,
        array $cacheEvents,
        array $outgoingRequests,
        array $queuedJobs,
    ): array {
        $totalDurationUs = (int) ($task->duration ?? 0);
        $builder = new SpanBuilder($task->recorded_at, $totalDurationUs);

        $allSpans = [];
        foreach ($queries as $q) {
            $allSpans[] = $builder->querySpan($q);
        }
        foreach ($mails as $m) {
            $allSpans[] = $builder->mailSpan($m);
        }
        foreach ($notifications as $n) {
            $allSpans[] = $builder->notificationSpan($n);
        }
        foreach ($cacheEvents as $c) {
            $allSpans[] = $builder->cacheSpan($c);
        }
        foreach ($outgoingRequests as $r) {
            $allSpans[] = $builder->outgoingRequestSpan($r);
        }
        foreach ($queuedJobs as $j) {
            $allSpans[] = $builder->queuedJobSpan($j);
        }

        usort($allSpans, fn ($a, $b) => $a['offset'] <=> $b['offset']);

        $stages = [
            SpanBuilder::buildStage('run', 'run', $task->name, $totalDurationUs, 0, $allSpans),
        ];

        $status = $task->status ?? 'processed';
        $variant = match ($status) {
            'failed' => 'error',
            'skipped' => 'warning',
            default => 'success',
        };

        return [$builder->buildExecution(
            $task->id,
            'scheduled task',
            $task->name,
            0,
            $variant,
            $stages,
        )];
    }
}
