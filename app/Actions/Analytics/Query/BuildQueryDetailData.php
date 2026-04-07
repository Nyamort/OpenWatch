<?php

namespace App\Actions\Analytics\Query;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildQueryDetailData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph, stats and paginated runs for a given sql_hash.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sqlHash, string $sort = 'date', string $direction = 'desc', int $page = 1): array
    {
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedHash = ClickHouseService::escape($sqlHash);

        $baseWhere = "WHERE environment_id = {$envId}
            AND sql_hash = {$escapedHash}
            AND recorded_at BETWEEN {$start} AND {$end}";

        $stats = $this->clickhouse->selectOne("
            SELECT
                any(sql_normalized) AS sql_normalized,
                any(connection) AS connection,
                count() AS count,
                toUInt64(if(isFinite(sum(duration)), sum(duration), 0)) AS total,
                toUInt32(if(isFinite(min(duration)), min(duration), 0)) AS min,
                toUInt32(if(isFinite(max(duration)), max(duration), 0)) AS max,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_queries
            {$baseWhere}
        ");

        $totalCount = (int) ($stats?->count ?? 0);

        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                count() AS calls,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_queries
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
                'calls' => (int) ($row?->calls ?? 0),
                'avg' => $row ? (int) $row->avg : null,
                'p95' => $row ? (int) $row->p95 : null,
            ];
        }

        $allowedSorts = ['date' => 'recorded_at', 'duration' => 'duration'];
        $orderCol = $allowedSorts[$sort] ?? 'recorded_at';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';
        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT id, recorded_at, execution_source, execution_preview, file, line, connection, duration
            FROM extraction_queries
            {$baseWhere}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $runs = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => Carbon::parse($row->recorded_at)->format('Y-m-d H:i:s'),
            'source' => $row->execution_source ?: null,
            'source_preview' => $row->execution_preview ?: null,
            'file' => $row->file ?: null,
            'line' => $row->line ? (int) $row->line : null,
            'connection' => $row->connection,
            'duration' => (int) $row->duration,
        ])->all();

        return [
            'graph' => $graph,
            'stats' => [
                'count' => $totalCount,
                'total' => $stats ? (int) $stats->total : null,
                'min' => $stats ? (int) $stats->min : null,
                'max' => $stats ? (int) $stats->max : null,
                'avg' => $stats ? (int) $stats->avg : null,
                'p95' => $stats ? (int) $stats->p95 : null,
                'sql_normalized' => $stats?->sql_normalized,
                'connection' => $stats?->connection,
            ],
            'runs' => $runs,
            'pagination' => $this->buildPaginationMeta($totalCount, $page),
        ];
    }
}
