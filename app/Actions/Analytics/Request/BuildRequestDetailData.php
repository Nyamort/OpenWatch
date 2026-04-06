<?php

namespace App\Actions\Analytics\Request;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildRequestDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single extraction_requests row with related queries, exceptions, and logs.
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

        return (new AnalyticsResponseBuilder)
            ->withSummary(array_merge((array) $request, ['mail_count' => count($mails)]))
            ->withRows([
                'queries' => $queries,
                'exceptions' => $exceptions,
                'logs' => $logs,
                'mails' => $mails,
                'notifications' => $notifications,
                'cache_events' => $cacheEvents,
                'outgoing_requests' => $outgoingRequests,
            ])
            ->build();
    }
}
