<?php

namespace App\Actions\Analytics\Notification;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildNotificationDetailData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph, stats and paginated runs for a given notification class.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $notificationClass, string $sort = 'date', string $direction = 'desc', int $page = 1): array
    {
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedClass = ClickHouseService::escape($notificationClass);

        $baseWhere = "WHERE environment_id = {$envId}
            AND class = {$escapedClass}
            AND recorded_at BETWEEN {$start} AND {$end}";

        $stats = $this->clickhouse->selectOne("
            SELECT
                count() AS count,
                toUInt32(if(isFinite(min(duration)), min(duration), 0)) AS min,
                toUInt32(if(isFinite(max(duration)), max(duration), 0)) AS max,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_notifications
            {$baseWhere}
        ");

        $totalCount = (int) ($stats?->count ?? 0);

        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                count() AS count,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_notifications
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
                'avg' => $row ? (int) $row->avg : null,
                'p95' => $row ? (int) $row->p95 : null,
            ];
        }

        $allowedSorts = ['date' => 'recorded_at', 'duration' => 'duration'];
        $orderCol = $allowedSorts[$sort] ?? 'recorded_at';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';
        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT id, recorded_at, execution_source, execution_preview, channel, duration
            FROM extraction_notifications
            {$baseWhere}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $runs = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => Carbon::parse($row->recorded_at)->format('Y-m-d H:i:s'),
            'source' => $row->execution_source ?: null,
            'source_preview' => $row->execution_preview ?: null,
            'channel' => $row->channel,
            'duration' => $row->duration !== null ? (int) $row->duration : null,
        ])->all();

        return [
            'graph' => $graph,
            'stats' => [
                'count' => $totalCount,
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
