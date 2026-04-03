<?php

namespace App\Actions\Analytics\Request;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildRequestRouteData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets, stats and paginated request list for a single route.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $routePath,
        string $method,
        string $sort = 'date',
        string $direction = 'desc',
        int $page = 1,
    ): array {
        $orgId = $ctx->organization->id;
        $projId = $ctx->project->id;
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedPath = ClickHouseService::escape($routePath);

        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}
            AND route_path = {$escapedPath}";

        if ($method !== '') {
            $escapedMethod = ClickHouseService::escape(strtoupper($method));
            $baseWhere .= " AND method = {$escapedMethod}";
        }

        // Global stats
        $stats = $this->clickhouse->selectOne("
            SELECT
                count() AS count,
                countIf(status_code < 400) AS `2xx`,
                countIf(status_code >= 400 AND status_code < 500) AS `4xx`,
                countIf(status_code >= 500) AS `5xx`,
                toUInt32(min(duration)) AS min,
                toUInt32(max(duration)) AS max,
                toUInt32(round(avg(duration))) AS avg,
                toUInt32(round(quantile(0.95)(duration))) AS p95
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
                toUInt32(round(avg(duration))) AS avg,
                toUInt32(round(quantile(0.95)(duration))) AS p95
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
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $requests = $this->fetchRequests($baseWhere, $sort, $direction, $totalCount, $page);

        return [
            'graph' => $graph,
            'requests' => $requests['data'],
            'pagination' => $requests['pagination'],
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
            'route_path' => $routePath,
            'method' => $method !== '' ? strtoupper($method) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRequests(string $baseWhere, string $sort, string $direction, int $total, int $page): array
    {
        $allowedSorts = [
            'date' => 'recorded_at',
            'status' => 'status_code',
            'duration' => 'duration',
        ];
        $orderCol = $allowedSorts[$sort] ?? 'recorded_at';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';
        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT id, method, url, status_code, duration, exceptions, queries, recorded_at
            FROM extraction_requests
            {$baseWhere}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => $row->recorded_at,
            'method' => $row->method,
            'url' => $row->url,
            'status_code' => (int) $row->status_code,
            'duration' => $row->duration,
            'exceptions' => (int) ($row->exceptions ?? 0),
            'queries' => (int) ($row->queries ?? 0),
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($total, $page),
        ];
    }
}
