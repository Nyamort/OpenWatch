<?php

namespace App\Actions\Analytics\OutgoingRequest;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildOutgoingRequestHostData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph, stats and paginated runs for a given host.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $host, string $sort = 'date', string $direction = 'desc', int $page = 1): array
    {
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedHost = ClickHouseService::escape($host);

        $baseWhere = "WHERE environment_id = {$envId}
            AND host = {$escapedHost}
            AND recorded_at BETWEEN {$start} AND {$end}";

        $stats = $this->clickhouse->selectOne("
            SELECT
                count() AS total,
                countIf(status_code IS NOT NULL AND status_code < 400) AS success,
                countIf(status_code >= 400 AND status_code < 500) AS count_4xx,
                countIf(status_code >= 500) AS count_5xx,
                toUInt32(if(isFinite(min(duration)), min(duration), 0)) AS min,
                toUInt32(if(isFinite(max(duration)), max(duration), 0)) AS max,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_outgoing_requests
            {$baseWhere}
        ");

        $totalCount = (int) ($stats?->total ?? 0);

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
                'avg' => $row ? (int) $row->avg : null,
                'p95' => $row ? (int) $row->p95 : null,
            ];
        }

        $allowedSorts = ['date' => 'recorded_at', 'duration' => 'duration', 'status' => 'status_code'];
        $orderCol = $allowedSorts[$sort] ?? 'recorded_at';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';
        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT id, recorded_at, execution_source, execution_preview, method, status_code, url, duration
            FROM extraction_outgoing_requests
            {$baseWhere}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $runs = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => Carbon::parse($row->recorded_at)->format('Y-m-d H:i:s'),
            'source' => $row->execution_source ?: null,
            'source_preview' => $row->execution_preview ?: null,
            'method' => $row->method ?: null,
            'status_code' => $row->status_code !== null ? (int) $row->status_code : null,
            'url' => $row->url ?: null,
            'duration' => $row->duration !== null ? (int) $row->duration : null,
        ])->all();

        return [
            'graph' => $graph,
            'stats' => [
                'total' => $totalCount,
                'success' => $stats ? (int) $stats->success : 0,
                'count_4xx' => $stats ? (int) $stats->count_4xx : 0,
                'count_5xx' => $stats ? (int) $stats->count_5xx : 0,
                'min' => $stats ? (int) $stats->min : null,
                'max' => $stats ? (int) $stats->max : null,
                'avg' => $stats ? (int) $stats->avg : null,
                'p95' => $stats ? (int) $stats->p95 : null,
            ],
            'runs' => $runs,
            'pagination' => $this->buildPaginationMeta($totalCount, $page),
        ];
    }
}
