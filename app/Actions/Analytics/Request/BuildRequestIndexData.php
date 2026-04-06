<?php

namespace App\Actions\Analytics\Request;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildRequestIndexData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets and global stats for request analytics.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sort = 'total', string $direction = 'desc', string $search = '', int $page = 1): array
    {
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
                count() AS count,
                countIf(status_code < 400) AS `2xx`,
                countIf(status_code >= 400 AND status_code < 500) AS `4xx`,
                countIf(status_code >= 500) AS `5xx`,
                toUInt32(min(duration)) AS min,
                toUInt32(max(duration)) AS max,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_requests
            {$baseWhere}
        ");

        $totalCount = (int) ($stats->count ?? 0);

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                count() AS count,
                countIf(status_code < 400) AS `2xx`,
                countIf(status_code >= 400 AND status_code < 500) AS `4xx`,
                countIf(status_code >= 500) AS `5xx`,
                toUInt32(min(duration)) AS min,
                toUInt32(max(duration)) AS max,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_requests
            {$baseWhere}
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
                'count' => (int) ($row?->count ?? 0),
                '2xx' => (int) ($row?->{'2xx'} ?? 0),
                '4xx' => (int) ($row?->{'4xx'} ?? 0),
                '5xx' => (int) ($row?->{'5xx'} ?? 0),
                'min' => $row?->min,
                'max' => $row?->max,
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $paths = $this->fetchPaths($orgId, $projId, $envId, $start, $end, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'paths' => $paths['data'],
            'pagination' => $paths['pagination'],
            'stats' => [
                'count' => $totalCount,
                '2xx' => (int) ($stats?->{'2xx'} ?? 0),
                '4xx' => (int) ($stats?->{'4xx'} ?? 0),
                '5xx' => (int) ($stats?->{'5xx'} ?? 0),
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'avg' => $stats->avg ?? null,
                'p95' => $stats->p95 ?? null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchPaths(int $orgId, int $projId, int $envId, string $start, string $end, string $sort, string $direction, string $search, int $page): array
    {
        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        if ($search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $baseWhere .= " AND route_path LIKE {$escaped}";
        }

        $allowedSorts = [
            'method' => 'methods',
            'path' => 'route_path',
            'total' => 'total',
            '2xx' => '`2xx`',
            '4xx' => '`4xx`',
            '5xx' => '`5xx`',
            'avg' => 'avg',
            'p95' => 'p95',
        ];
        $orderCol = $allowedSorts[$sort] ?? $allowedSorts['total'];
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';

        $totalRoutes = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(route_path) FROM extraction_requests {$baseWhere}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                route_path,
                any(route_methods) AS methods,
                count() AS total,
                countIf(status_code < 400) AS `2xx`,
                countIf(status_code >= 400 AND status_code < 500) AS `4xx`,
                countIf(status_code >= 500) AS `5xx`,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_requests
            {$baseWhere}
            GROUP BY route_path
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'methods' => array_values(array_filter(explode('|', $row->methods ?? ''))),
            'path' => $row->route_path ?: null,
            '2xx' => (int) ($row->{'2xx'} ?? 0),
            '4xx' => (int) ($row->{'4xx'} ?? 0),
            '5xx' => (int) ($row->{'5xx'} ?? 0),
            'total' => (int) $row->total,
            'avg' => $row->avg,
            'p95' => $row->p95,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalRoutes, $page),
        ];
    }
}
