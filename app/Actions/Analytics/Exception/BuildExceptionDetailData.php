<?php

namespace App\Actions\Analytics\Exception;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildExceptionDetailData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Resolve group_key to representative record (latest) and all occurrences paginated.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $groupKey, int $page = 1): array
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

        // All-time aggregate metrics (independent of the selected period)
        $allTimeAggregates = $this->clickhouse->selectOne("
            SELECT
                count() AS occurrences,
                uniqExact(user) AS impacted_users,
                uniqExact(server) AS servers,
                max(recorded_at) AS last_seen,
                min(recorded_at) AS first_seen,
                countIf(recorded_at >= now() - INTERVAL 7 DAY) AS occurrences_7d,
                countIf(recorded_at >= now() - INTERVAL 1 DAY) AS occurrences_24h
            FROM extraction_exceptions
            WHERE environment_id = {$envId}
              AND group_key = {$escapedKey}
        ");

        // Period-scoped aggregate metrics (for chart and stats only)
        $aggregates = $this->clickhouse->selectOne("
            SELECT
                count() AS occurrences,
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

        $offset = $this->pageOffset($page);

        $occurrences = $this->clickhouse->select("
            SELECT
                e.*,
                u.username AS user_email
            FROM extraction_exceptions e
            ANY LEFT JOIN (
                SELECT user_id, any(username) AS username
                FROM extraction_user_activities
                WHERE environment_id = {$envId}
                GROUP BY user_id
            ) u ON u.user_id = e.user
            WHERE e.environment_id = {$envId}
              AND e.group_key = {$escapedKey}
              AND e.recorded_at BETWEEN {$start} AND {$end}
            ORDER BY e.recorded_at DESC
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $traceId = ClickHouseService::escape($representative->trace_id ?? '');

        $relatedRequests = $this->clickhouse->select("
            SELECT id, route_path, method, status_code, recorded_at
            FROM extraction_requests
            WHERE environment_id = {$envId}
              AND trace_id = {$traceId}
        ")->toArray();

        $rows = $occurrences->map(function ($row) {
            $data = (array) $row;
            $data['user'] = $row->user_email !== '' && $row->user_email !== null
                ? $row->user_email
                : null;
            unset($data['user_email']);

            return $data;
        })->all();

        return (new AnalyticsResponseBuilder)
            ->withSummary(array_merge((array) $representative, [
                'related_requests' => $relatedRequests,
                'last_seen' => $allTimeAggregates?->last_seen,
                'first_seen' => $allTimeAggregates?->first_seen,
                'first_reported_in' => $firstDeploy ?: null,
                'impacted_users' => (int) ($allTimeAggregates?->impacted_users ?? 0),
                'occurrences' => (int) ($allTimeAggregates?->occurrences ?? 0),
                'occurrences_7d' => (int) ($allTimeAggregates?->occurrences_7d ?? 0),
                'occurrences_24h' => (int) ($allTimeAggregates?->occurrences_24h ?? 0),
                'servers' => (int) ($allTimeAggregates?->servers ?? 0),
            ]))
            ->withRows($rows)
            ->withPagination($this->buildPaginationMeta($total, $page))
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
