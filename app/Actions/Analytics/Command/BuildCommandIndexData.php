<?php

namespace App\Actions\Analytics\Command;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildCommandIndexData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets, global stats and paginated command table.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sort = 'name', string $direction = 'desc', string $search = '', int $page = 1): array
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
                countIf(exit_code = 0) AS successful,
                countIf(exit_code != 0) AS failed,
                toUInt32(min(duration)) AS min,
                toUInt32(max(duration)) AS max,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_commands
            {$baseWhere}
        ");

        $totalCount = (int) ($stats->count ?? 0);

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                count() AS count,
                countIf(exit_code = 0) AS successful,
                countIf(exit_code != 0) AS failed,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_commands
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
                'successful' => (int) ($row?->successful ?? 0),
                'failed' => (int) ($row?->failed ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $commands = $this->fetchCommands($orgId, $projId, $envId, $start, $end, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'commands' => $commands['data'],
            'pagination' => $commands['pagination'],
            'stats' => [
                'count' => $totalCount,
                'successful' => (int) ($stats?->successful ?? 0),
                'failed' => (int) ($stats?->failed ?? 0),
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
    private function fetchCommands(int $orgId, int $projId, int $envId, string $start, string $end, string $sort, string $direction, string $search, int $page): array
    {
        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        if ($search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $baseWhere .= " AND name LIKE {$escaped}";
        }

        $allowedSorts = ['name' => 'name', 'total' => 'total', 'successful' => 'successful', 'failed' => 'failed', 'avg' => 'avg', 'p95' => 'p95'];
        $orderCol = $allowedSorts[$sort] ?? 'total';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';

        $totalCommands = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(name) FROM extraction_commands {$baseWhere}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                name,
                count() AS total,
                countIf(exit_code = 0) AS successful,
                countIf(exit_code != 0) AS failed,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_commands
            {$baseWhere}
            GROUP BY name
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'name' => $row->name ?: null,
            'total' => (int) $row->total,
            'successful' => (int) ($row->successful ?? 0),
            'failed' => (int) ($row->failed ?? 0),
            'avg' => $row->avg,
            'p95' => $row->p95,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalCommands, $page),
        ];
    }
}
