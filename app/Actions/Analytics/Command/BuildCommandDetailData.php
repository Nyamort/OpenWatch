<?php

namespace App\Actions\Analytics\Command;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildCommandDetailData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph, stats and paginated runs for a single command name.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $name, string $sort = 'date', string $direction = 'desc', int $page = 1): array
    {
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedName = ClickHouseService::escape($name);

        $baseWhere = "WHERE environment_id = {$envId}
            AND name = {$escapedName}
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

        // Time-bucketed graph
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
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
                'successful' => (int) ($row?->successful ?? 0),
                'failed' => (int) ($row?->failed ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $allowedSorts = ['date' => 'recorded_at', 'exit_code' => 'exit_code', 'duration' => 'duration'];
        $orderCol = $allowedSorts[$sort] ?? 'recorded_at';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';
        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT id, recorded_at, name, exit_code, duration
            FROM extraction_commands
            {$baseWhere}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => Carbon::parse($row->recorded_at)->format('Y-m-d H:i:s'),
            'name' => $row->name,
            'exit_code' => $row->exit_code,
            'duration' => $row->duration,
        ])->all();

        return [
            'graph' => $graph,
            'runs' => $data,
            'pagination' => $this->buildPaginationMeta($totalCount, $page),
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
}
