<?php

namespace App\Actions\Analytics\CacheEvent;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildCacheEventIndexData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets, global stats, and paginated cache key table.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $sort = 'total',
        string $direction = 'desc',
        string $search = '',
        int $page = 1,
    ): array {
        $orgId = $ctx->organization->id;
        $projId = $ctx->project->id;
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);

        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        // Global stats
        $stats = $this->clickhouse->selectOne("
            SELECT
                count() AS total,
                countIf(type = 'hit') AS hits,
                countIf(type = 'miss') AS misses,
                countIf(type = 'write') AS writes,
                countIf(type = 'delete') AS deletes,
                countIf(type IN ('write-failure', 'delete-failure')) AS failures,
                countIf(type = 'write-failure') AS write_failures,
                countIf(type = 'delete-failure') AS delete_failures
            FROM extraction_cache_events
            {$baseWhere}
        ");

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                countIf(type = 'hit') AS hits,
                countIf(type = 'miss') AS misses,
                countIf(type = 'write') AS writes,
                countIf(type = 'delete') AS deletes,
                countIf(type = 'write-failure') AS write_failures,
                countIf(type = 'delete-failure') AS delete_failures
            FROM extraction_cache_events
            {$baseWhere}
            GROUP BY bucket_slot
            ORDER BY bucket_slot
        ")->keyBy('bucket_slot');

        $eventsGraph = [];
        $failuresGraph = [];
        $startSlot = (int) floor(Carbon::parse($period->start)->utc()->timestamp / $bucketSeconds);
        $endSlot = (int) floor(Carbon::parse($period->end)->utc()->timestamp / $bucketSeconds);

        for ($slot = $startSlot; $slot <= $endSlot; $slot++) {
            $row = $bucketMap->get($slot);
            $bucket = Carbon::createFromTimestampUTC($slot * $bucketSeconds)->format('Y-m-d H:i:s');
            $eventsGraph[] = [
                'bucket' => $bucket,
                'hits' => (int) ($row?->hits ?? 0),
                'misses' => (int) ($row?->misses ?? 0),
                'writes' => (int) ($row?->writes ?? 0),
                'deletes' => (int) ($row?->deletes ?? 0),
            ];
            $failuresGraph[] = [
                'bucket' => $bucket,
                'write_failures' => (int) ($row?->write_failures ?? 0),
                'delete_failures' => (int) ($row?->delete_failures ?? 0),
            ];
        }

        $keys = $this->fetchKeys($orgId, $projId, $envId, $start, $end, $sort, $direction, $search, $page);

        return [
            'events_graph' => $eventsGraph,
            'failures_graph' => $failuresGraph,
            'stats' => [
                'total' => (int) ($stats->total ?? 0),
                'hits' => (int) ($stats->hits ?? 0),
                'misses' => (int) ($stats->misses ?? 0),
                'writes' => (int) ($stats->writes ?? 0),
                'deletes' => (int) ($stats->deletes ?? 0),
                'failures' => (int) ($stats->failures ?? 0),
                'write_failures' => (int) ($stats->write_failures ?? 0),
                'delete_failures' => (int) ($stats->delete_failures ?? 0),
            ],
            'keys' => $keys['data'],
            'pagination' => $keys['pagination'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchKeys(int $orgId, int $projId, int $envId, string $start, string $end, string $sort, string $direction, string $search, int $page): array
    {
        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        if ($search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $baseWhere .= " AND key LIKE {$escaped}";
        }

        $allowedSorts = [
            'key' => 'key',
            'hit_pct' => 'hit_pct',
            'hits' => 'hits',
            'misses' => 'misses',
            'writes' => 'writes',
            'deletes' => 'deletes',
            'failures' => 'failures',
            'total' => 'total',
        ];
        $orderCol = $allowedSorts[$sort] ?? 'total';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';

        $totalKeys = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(key) FROM extraction_cache_events {$baseWhere}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                key,
                count() AS total,
                countIf(type = 'hit') AS hits,
                countIf(type = 'miss') AS misses,
                countIf(type = 'write') AS writes,
                countIf(type = 'delete') AS deletes,
                countIf(type IN ('write-failure', 'delete-failure')) AS failures,
                toFloat64(round(
                    countIf(type = 'hit') * 100.0
                    / nullIf(countIf(type IN ('hit', 'miss')), 0)
                , 1)) AS hit_pct
            FROM extraction_cache_events
            {$baseWhere}
            GROUP BY key
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'key' => $row->key,
            'hit_pct' => $row->hit_pct,
            'hits' => (int) $row->hits,
            'misses' => (int) $row->misses,
            'writes' => (int) $row->writes,
            'deletes' => (int) $row->deletes,
            'failures' => (int) $row->failures,
            'total' => (int) $row->total,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalKeys, $page),
        ];
    }
}
