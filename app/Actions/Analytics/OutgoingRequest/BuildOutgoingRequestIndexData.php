<?php

namespace App\Actions\Analytics\OutgoingRequest;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildOutgoingRequestIndexData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets, global stats, and paginated host table.
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
                countIf(status_code IS NOT NULL AND status_code < 400) AS success,
                countIf(status_code >= 400 AND status_code < 500) AS count_4xx,
                countIf(status_code >= 500) AS count_5xx,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(min(duration)) AS min,
                toUInt32(max(duration)) AS max,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_outgoing_requests
            {$baseWhere}
        ");

        $totalCount = (int) ($stats->total ?? 0);

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                countIf(status_code IS NOT NULL AND status_code < 400) AS success,
                countIf(status_code >= 400 AND status_code < 500) AS count_4xx,
                countIf(status_code >= 500) AS count_5xx,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_outgoing_requests
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
                'success' => (int) ($row?->success ?? 0),
                'count_4xx' => (int) ($row?->count_4xx ?? 0),
                'count_5xx' => (int) ($row?->count_5xx ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $hosts = $this->fetchHosts($orgId, $projId, $envId, $start, $end, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'stats' => [
                'total' => $totalCount,
                'success' => (int) ($stats->success ?? 0),
                'count_4xx' => (int) ($stats->count_4xx ?? 0),
                'count_5xx' => (int) ($stats->count_5xx ?? 0),
                'avg' => $stats->avg ?? null,
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'p95' => $stats->p95 ?? null,
            ],
            'hosts' => $hosts['data'],
            'pagination' => $hosts['pagination'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchHosts(int $orgId, int $projId, int $envId, string $start, string $end, string $sort, string $direction, string $search, int $page): array
    {
        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        if ($search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $baseWhere .= " AND host LIKE {$escaped}";
        }

        $allowedSorts = ['host' => 'host', 'success' => 'success', 'count_4xx' => 'count_4xx', 'count_5xx' => 'count_5xx', 'total' => 'total', 'avg' => 'avg', 'p95' => 'p95'];
        $orderCol = $allowedSorts[$sort] ?? 'total';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';

        $totalHosts = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(host) FROM extraction_outgoing_requests {$baseWhere}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                host,
                count() AS total,
                countIf(status_code IS NOT NULL AND status_code < 400) AS success,
                countIf(status_code >= 400 AND status_code < 500) AS count_4xx,
                countIf(status_code >= 500) AS count_5xx,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_outgoing_requests
            {$baseWhere}
            GROUP BY host
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'host' => $row->host,
            'success' => (int) $row->success,
            'count_4xx' => (int) $row->count_4xx,
            'count_5xx' => (int) $row->count_5xx,
            'total' => (int) $row->total,
            'avg' => $row->avg,
            'p95' => $row->p95,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalHosts, $page),
        ];
    }
}
