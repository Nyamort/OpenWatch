<?php

namespace App\Actions\Analytics\Request;

use App\Concerns\FetchesUserDetails;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\SpanBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildRequestDetailData
{
    use FetchesUserDetails;

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

        $userDetails = $this->fetchUserDetails($orgId, $request->user ?? null);
        $summaryArray = array_merge((array) $request, [
            'mail_count' => count($mails),
            'user_name' => $userDetails?->name,
            'user_email' => $userDetails?->username,
        ]);
        $executions = $this->buildExecutions($request, $queries, $exceptions, $logs, $mails, $notifications, $cacheEvents, $outgoingRequests);

        return (new AnalyticsResponseBuilder)
            ->withSummary($summaryArray)
            ->withRows(['executions' => $executions])
            ->build();
    }

    /**
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
        $builder = new SpanBuilder($request->recorded_at, $totalDurationUs);

        $spansByStage = SpanBuilder::groupByStage(
            [$queries, $builder->querySpan(...)],
            [$exceptions, $builder->exceptionSpan(...)],
            [$logs, $builder->logSpan(...)],
            [$mails, $builder->mailSpan(...)],
            [$notifications, $builder->notificationSpan(...)],
            [$cacheEvents, $builder->cacheSpan(...)],
            [$outgoingRequests, $builder->outgoingRequestSpan(...)],
        );

        $stages = SpanBuilder::buildStagesFromPhases($spansByStage, [
            ['id' => 'bootstrap', 'name' => 'bootstrap', 'duration' => (int) ($request->bootstrap ?? 0)],
            ['id' => 'before_middleware', 'name' => 'middleware', 'duration' => (int) ($request->before_middleware ?? 0)],
            ['id' => 'action', 'name' => 'controller', 'duration' => (int) ($request->action ?? 0), 'description' => $request->route_action ?? ''],
            ['id' => 'render', 'name' => 'render', 'duration' => (int) ($request->render ?? 0)],
            ['id' => 'after_middleware', 'name' => 'middleware', 'duration' => (int) ($request->after_middleware ?? 0)],
            ['id' => 'sending', 'name' => 'sending', 'duration' => (int) ($request->sending ?? 0)],
            ['id' => 'terminating', 'name' => 'terminating', 'duration' => (int) ($request->terminating ?? 0)],
        ]);

        $statusCode = (int) ($request->status_code ?? 200);

        return [$builder->buildExecution(
            $request->id,
            'request',
            $request->route_path ?? $request->url,
            $statusCode,
            $statusCode < 400 ? 'success' : ($statusCode < 500 ? 'warning' : 'error'),
            $stages,
        )];
    }
}
