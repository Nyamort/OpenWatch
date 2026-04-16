<?php

namespace App\Actions\Analytics\Exception;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildExceptionDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Resolve group_key to representative record (latest) and all occurrences paginated.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $groupKey): array
    {
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedKey = ClickHouseService::escape($groupKey);

        $representative = $this->clickhouse->selectOne("
            SELECT *
            FROM extraction_exceptions
            WHERE environment_id = {$envId}
              AND group_key = {$escapedKey}
            ORDER BY recorded_at DESC
            LIMIT 1
        ");

        if ($representative === null) {
            abort(404, 'Exception group not found.');
        }

        // Aggregate metrics for the period
        $aggregates = $this->clickhouse->selectOne("
            SELECT
                count() AS occurrences,
                uniqExact(user) AS impacted_users,
                uniqExact(server) AS servers,
                max(recorded_at) AS last_seen,
                min(recorded_at) AS first_seen,
                sum(handled) AS handled_count,
                sum(1 - handled) AS unhandled_count
            FROM extraction_exceptions
            WHERE environment_id = {$envId}
              AND group_key = {$escapedKey}
              AND recorded_at BETWEEN {$start} AND {$end}
        ");

        // First deploy this group was ever seen in
        $firstDeploy = $this->clickhouse->selectValue("
            SELECT deploy
            FROM extraction_exceptions
            WHERE environment_id = {$envId}
              AND group_key = {$escapedKey}
            ORDER BY recorded_at ASC
            LIMIT 1
        ");

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                sum(handled) AS handled_count,
                sum(1 - handled) AS unhandled_count
            FROM extraction_exceptions
            WHERE environment_id = {$envId}
              AND group_key = {$escapedKey}
              AND recorded_at BETWEEN {$start} AND {$end}
            GROUP BY bucket_slot
            ORDER BY bucket_slot
        ")->keyBy('bucket_slot');

        $graph = [];
        $startSlot = (int) floor(Carbon::parse($period->start)->utc()->timestamp / $bucketSeconds);
        $endSlot = (int) floor(Carbon::parse($period->end)->utc()->timestamp / $bucketSeconds);

        for ($slot = $startSlot; $slot <= $endSlot; $slot++) {
            $row = $bucketMap->get($slot);
            $graph[] = [
                'bucket' => Carbon::createFromTimestampUTC($slot * $bucketSeconds)->format('Y-m-d H:i:s'),
                'handled' => (int) ($row?->handled_count ?? 0),
                'unhandled' => (int) ($row?->unhandled_count ?? 0),
            ];
        }

        $total = (int) ($aggregates?->occurrences ?? 0);

        $occurrences = $this->clickhouse->select("
            SELECT *
            FROM extraction_exceptions
            WHERE environment_id = {$envId}
              AND group_key = {$escapedKey}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at DESC
            LIMIT 50
        ");

        $traceId = ClickHouseService::escape($representative->trace_id ?? '');

        $relatedRequests = $this->clickhouse->select("
            SELECT id, route_path, method, status_code, recorded_at
            FROM extraction_requests
            WHERE environment_id = {$envId}
              AND trace_id = {$traceId}
        ")->toArray();

        return (new AnalyticsResponseBuilder)
            ->withSummary(array_merge((array) $representative, [
                'related_requests' => $relatedRequests,
                'last_seen' => $aggregates?->last_seen,
                'first_seen' => $aggregates?->first_seen,
                'first_reported_in' => $firstDeploy ?: null,
                'impacted_users' => (int) ($aggregates?->impacted_users ?? 0),
                'occurrences' => $total,
                'servers' => (int) ($aggregates?->servers ?? 0),
            ]))
            ->withRows($occurrences->toArray())
            ->withPagination([
                'current_page' => 1,
                'last_page' => (int) ceil($total / 50),
                'per_page' => 50,
                'total' => $total,
            ])
            ->build() + [
                'graph' => $graph,
                'stats' => [
                    'count' => $total,
                    'handled' => (int) ($aggregates?->handled_count ?? 0),
                    'unhandled' => (int) ($aggregates?->unhandled_count ?? 0),
                ],
            ];
    }
}
