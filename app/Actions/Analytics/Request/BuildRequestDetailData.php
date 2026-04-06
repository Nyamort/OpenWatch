<?php

namespace App\Actions\Analytics\Request;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildRequestDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single extraction_requests row with related events, structured as executions.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, string $requestId): array
    {
        $orgId = $ctx->organization->id;
        $escapedId = ClickHouseService::escape($requestId);

        $request = $this->clickhouse->selectOne("
            SELECT *
            FROM extraction_requests
            WHERE id = {$escapedId}
              AND organization_id = {$orgId}
            LIMIT 1
        ");

        if ($request === null) {
            abort(404, 'Request not found.');
        }

        $traceId = ClickHouseService::escape($request->trace_id ?? '');

        $queries = $this->clickhouse->select("
            SELECT *
            FROM extraction_queries
            WHERE trace_id = {$traceId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

        $exceptions = $this->clickhouse->select("
            SELECT *
            FROM extraction_exceptions
            WHERE trace_id = {$traceId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

        $logs = $this->clickhouse->select("
            SELECT *
            FROM extraction_logs
            WHERE execution_id = {$traceId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

        $mails = $this->clickhouse->select("
            SELECT *
            FROM extraction_mails
            WHERE trace_id = {$traceId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

        $notifications = $this->clickhouse->select("
            SELECT *
            FROM extraction_notifications
            WHERE trace_id = {$traceId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

        $cacheEvents = $this->clickhouse->select("
            SELECT *
            FROM extraction_cache_events
            WHERE trace_id = {$traceId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

        $outgoingRequests = $this->clickhouse->select("
            SELECT *
            FROM extraction_outgoing_requests
            WHERE trace_id = {$traceId}
              AND organization_id = {$orgId}
            ORDER BY recorded_at
        ")->toArray();

        $summaryArray = array_merge((array) $request, ['mail_count' => count($mails)]);
        $executions = $this->buildExecutions($request, $queries, $exceptions, $logs, $mails, $notifications, $cacheEvents, $outgoingRequests);

        return (new AnalyticsResponseBuilder)
            ->withSummary($summaryArray)
            ->withRows(['executions' => $executions])
            ->build();
    }

    /**
     * Build the structured executions payload with pre-computed offsets (in microseconds).
     *
     * @param  array<int, object>  $queries
     * @param  array<int, object>  $exceptions
     * @param  array<int, object>  $logs
     * @param  array<int, object>  $mails
     * @param  array<int, object>  $notifications
     * @param  array<int, object>  $cacheEvents
     * @param  array<int, object>  $outgoingRequests
     * @return array<int, array<string, mixed>>
     */
    private function buildExecutions(
        object $request,
        array $queries,
        array $exceptions,
        array $logs,
        array $mails,
        array $notifications,
        array $cacheEvents,
        array $outgoingRequests,
    ): array {
        $totalDurationUs = (int) ($request->duration ?? 0);
        $requestEndUs = (int) Carbon::parse($request->recorded_at)->getPreciseTimestamp(6);
        $requestStartUs = $requestEndUs - $totalDurationUs;

        $toOffset = function (string $recordedAt, int $eventDurationUs = 0) use ($requestStartUs, $totalDurationUs): int {
            $ts = (int) Carbon::parse($recordedAt)->getPreciseTimestamp(6);

            return max(0, min($totalDurationUs, $ts - $requestStartUs - $eventDurationUs));
        };

        $spansByStage = [];

        foreach ($queries as $q) {
            $duration = (int) $q->duration;
            $spansByStage[$q->execution_stage][] = [
                'group' => $q->sql_hash ?? '',
                'span_type' => 'query',
                'timestamp' => $q->recorded_at,
                'duration' => $duration,
                'offset' => $toOffset($q->recorded_at, $duration),
                'name' => 'query',
                'description' => $q->sql_normalized,
                'connection' => $q->connection,
                'connection_type' => $q->connection_type,
            ];
        }

        foreach ($exceptions as $e) {
            $spansByStage[$e->execution_stage][] = [
                'span_type' => 'exception',
                'timestamp' => $e->recorded_at,
                'duration' => 0,
                'offset' => $toOffset($e->recorded_at),
                'name' => 'exception',
                'description' => $e->class,
                'message' => $e->message,
                'handled' => $e->handled,
            ];
        }

        foreach ($logs as $l) {
            $spansByStage[$l->execution_stage][] = [
                'span_type' => 'log',
                'timestamp' => $l->recorded_at,
                'duration' => 0,
                'offset' => $toOffset($l->recorded_at),
                'name' => $l->level,
                'description' => $l->message,
            ];
        }

        foreach ($mails as $m) {
            $duration = (int) $m->duration;
            $spansByStage[$m->execution_stage][] = [
                'span_type' => 'mail',
                'timestamp' => $m->recorded_at,
                'duration' => $duration,
                'offset' => $toOffset($m->recorded_at, $duration),
                'name' => 'mail',
                'description' => $m->subject ?: $m->class,
            ];
        }

        foreach ($notifications as $n) {
            $duration = (int) $n->duration;
            $spansByStage[$n->execution_stage][] = [
                'span_type' => 'notification',
                'timestamp' => $n->recorded_at,
                'duration' => $duration,
                'offset' => $toOffset($n->recorded_at, $duration),
                'name' => 'notification',
                'description' => $n->class,
                'channel' => $n->channel,
            ];
        }

        foreach ($cacheEvents as $c) {
            $duration = (int) $c->duration;
            $spansByStage[$c->execution_stage][] = [
                'span_type' => 'cache',
                'timestamp' => $c->recorded_at,
                'duration' => $duration,
                'offset' => $toOffset($c->recorded_at, $duration),
                'name' => $c->type,
                'description' => $c->key,
            ];
        }

        foreach ($outgoingRequests as $r) {
            $duration = (int) $r->duration;
            $spansByStage[$r->execution_stage][] = [
                'span_type' => 'outgoing_request',
                'timestamp' => $r->recorded_at,
                'duration' => $duration,
                'offset' => $toOffset($r->recorded_at, $duration),
                'name' => $r->method,
                'description' => $r->url,
                'status' => $r->status_code,
            ];
        }

        $phases = [
            ['id' => 'bootstrap', 'name' => 'bootstrap', 'duration' => (int) ($request->bootstrap ?? 0)],
            ['id' => 'before_middleware', 'name' => 'middleware', 'duration' => (int) ($request->before_middleware ?? 0)],
            ['id' => 'action', 'name' => 'controller', 'duration' => (int) ($request->action ?? 0), 'description' => $request->route_action ?? ''],
            ['id' => 'render', 'name' => 'render', 'duration' => (int) ($request->render ?? 0)],
            ['id' => 'after_middleware', 'name' => 'middleware', 'duration' => (int) ($request->after_middleware ?? 0)],
            ['id' => 'sending', 'name' => 'sending', 'duration' => (int) ($request->sending ?? 0)],
            ['id' => 'terminating', 'name' => 'terminating', 'duration' => (int) ($request->terminating ?? 0)],
        ];

        $cursor = 0;
        $stages = [];
        foreach ($phases as $phase) {
            if ($phase['duration'] <= 0) {
                continue;
            }
            $stageSpans = $spansByStage[$phase['id']] ?? [];
            usort($stageSpans, fn ($a, $b) => $a['offset'] <=> $b['offset']);

            $stages[] = [
                'id' => $phase['id'],
                'name' => $phase['name'],
                'description' => $phase['description'] ?? '',
                'duration' => $phase['duration'],
                'offset' => $cursor,
                'spans' => $stageSpans,
            ];

            $cursor += $phase['duration'];
        }

        $statusCode = (int) ($request->status_code ?? 200);

        return [
            [
                'id' => $request->id,
                'name' => 'request',
                'description' => $request->route_path ?? $request->url,
                'status' => $statusCode,
                'duration' => $totalDurationUs,
                'offset' => 0,
                'variant' => $statusCode < 400 ? 'success' : ($statusCode < 500 ? 'warning' : 'error'),
                'stages' => $stages,
            ],
        ];
    }
}
