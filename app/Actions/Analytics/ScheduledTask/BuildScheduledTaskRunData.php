<?php

namespace App\Actions\Analytics\ScheduledTask;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildScheduledTaskRunData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph, stats and paginated runs for a scheduled task (name + cron).
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $name,
        string $cron,
        string $sort = 'date',
        string $direction = 'desc',
        int $page = 1,
    ): array {
        $orgId = $ctx->organization->id;
        $projId = $ctx->project->id;
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedName = ClickHouseService::escape($name);
        $escapedCron = ClickHouseService::escape($cron);

        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND name = {$escapedName}
            AND cron = {$escapedCron}
            AND recorded_at BETWEEN {$start} AND {$end}";

        // Global stats
        $stats = $this->clickhouse->selectOne("
            SELECT
                count() AS count,
                countIf(status = 'processed') AS processed,
                countIf(status = 'skipped') AS skipped,
                countIf(status = 'failed') AS failed,
                toFloat64(min(duration)) AS min,
                toFloat64(max(duration)) AS max,
                toFloat64(round(avgIf(duration, status != 'skipped'), 2)) AS avg,
                toFloat64(quantileIf(0.95)(duration, status != 'skipped')) AS p95
            FROM extraction_scheduled_tasks
            {$baseWhere}
        ");

        $totalCount = (int) ($stats->count ?? 0);

        // Time-bucketed graph
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                countIf(status = 'processed') AS processed,
                countIf(status = 'skipped') AS skipped,
                countIf(status = 'failed') AS failed,
                toFloat64(round(avgIf(duration, status != 'skipped'), 2)) AS avg,
                toFloat64(quantileIf(0.95)(duration, status != 'skipped')) AS p95
            FROM extraction_scheduled_tasks
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
                'processed' => (int) ($row?->processed ?? 0),
                'skipped' => (int) ($row?->skipped ?? 0),
                'failed' => (int) ($row?->failed ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $allowedSorts = ['date' => 'recorded_at', 'status' => 'status', 'duration' => 'duration'];
        $orderCol = $allowedSorts[$sort] ?? 'recorded_at';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';
        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT id, recorded_at, status, duration
            FROM extraction_scheduled_tasks
            {$baseWhere}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => Carbon::parse($row->recorded_at)->format('Y-m-d H:i:s'),
            'status' => $row->status,
            'duration' => $row->duration,
        ])->all();

        return [
            'graph' => $graph,
            'runs' => $data,
            'pagination' => $this->buildPaginationMeta($totalCount, $page),
            'stats' => [
                'count' => $totalCount,
                'processed' => (int) ($stats?->processed ?? 0),
                'skipped' => (int) ($stats?->skipped ?? 0),
                'failed' => (int) ($stats?->failed ?? 0),
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'avg' => $stats->avg ?? null,
                'p95' => $stats->p95 ?? null,
            ],
        ];
    }
}
