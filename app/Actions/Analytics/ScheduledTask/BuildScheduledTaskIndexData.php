<?php

namespace App\Actions\Analytics\ScheduledTask;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;
use Cron\CronExpression;

class BuildScheduledTaskIndexData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets, global stats and paginated scheduled-task table.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sort = 'task', string $direction = 'asc', string $search = '', int $page = 1): array
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
                countIf(status = 'processed') AS processed,
                countIf(status = 'skipped') AS skipped,
                countIf(status = 'failed') AS failed,
                toUInt32(if(isFinite(avgIf(duration, status != 'skipped')), round(avgIf(duration, status != 'skipped')), 0)) AS avg,
                toUInt32(if(isFinite(quantileIf(0.95)(duration, status != 'skipped')), round(quantileIf(0.95)(duration, status != 'skipped')), 0)) AS p95
            FROM extraction_scheduled_tasks
            {$baseWhere}
        ");

        $totalCount = (int) ($stats->count ?? 0);

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                countIf(status = 'processed') AS processed,
                countIf(status = 'skipped') AS skipped,
                countIf(status = 'failed') AS failed,
                toUInt32(if(isFinite(avgIf(duration, status != 'skipped')), round(avgIf(duration, status != 'skipped')), 0)) AS avg,
                toUInt32(if(isFinite(quantileIf(0.95)(duration, status != 'skipped')), round(quantileIf(0.95)(duration, status != 'skipped')), 0)) AS p95
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

        $tasks = $this->fetchTasks($orgId, $projId, $envId, $start, $end, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'tasks' => $tasks['data'],
            'pagination' => $tasks['pagination'],
            'stats' => [
                'count' => $totalCount,
                'processed' => (int) ($stats?->processed ?? 0),
                'skipped' => (int) ($stats?->skipped ?? 0),
                'failed' => (int) ($stats?->failed ?? 0),
                'avg' => $stats->avg ?? null,
                'p95' => $stats->p95 ?? null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchTasks(int $orgId, int $projId, int $envId, string $start, string $end, string $sort, string $direction, string $search, int $page): array
    {
        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        if ($search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $baseWhere .= " AND name LIKE {$escaped}";
        }

        $allowedSorts = ['task' => 'name', 'processed' => 'processed', 'skipped' => 'skipped', 'failed' => 'failed', 'total' => 'total', 'avg' => 'avg', 'p95' => 'p95'];
        $orderCol = $allowedSorts[$sort] ?? 'name';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';

        $totalTasks = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(name) FROM extraction_scheduled_tasks {$baseWhere}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                name,
                any(cron) AS cron,
                count() AS total,
                countIf(status = 'processed') AS processed,
                countIf(status = 'skipped') AS skipped,
                countIf(status = 'failed') AS failed,
                toUInt32(if(isFinite(avgIf(duration, status != 'skipped')), round(avgIf(duration, status != 'skipped')), 0)) AS avg,
                toUInt32(if(isFinite(quantileIf(0.95)(duration, status != 'skipped')), round(quantileIf(0.95)(duration, status != 'skipped')), 0)) AS p95
            FROM extraction_scheduled_tasks
            {$baseWhere}
            GROUP BY name
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $now = Carbon::now();

        $data = $rows->map(function ($row) use ($now) {
            $nextRun = null;

            try {
                if ($row->cron) {
                    $expr = new CronExpression($row->cron);
                    $nextRun = $expr->getNextRunDate($now)->format('Y-m-d H:i:s');
                }
            } catch (\Throwable) {
                // Invalid cron expression — leave null
            }

            return [
                'name' => $row->name ?: null,
                'cron' => $row->cron ?: null,
                'next_run' => $nextRun,
                'total' => (int) $row->total,
                'processed' => (int) ($row->processed ?? 0),
                'skipped' => (int) ($row->skipped ?? 0),
                'failed' => (int) ($row->failed ?? 0),
                'avg' => $row->avg,
                'p95' => $row->p95,
            ];
        })->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalTasks, $page),
        ];
    }
}
