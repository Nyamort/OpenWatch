<?php

namespace App\Actions\Analytics\ScheduledTask;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildScheduledTaskIndexData
{
    use PaginatesAnalyticsQuery;

    /**
     * Build graph buckets, global stats and paginated scheduled-task table.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sort = 'task', string $direction = 'asc', string $search = '', int $page = 1): array
    {
        $base = DB::table('extraction_scheduled_tasks')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        // Global stats
        $stats = (clone $base)->selectRaw("
            COUNT(*) as count,
            SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
            SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) as skipped,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            CAST(ROUND(AVG(CASE WHEN status != 'skipped' THEN duration END), 2) AS DOUBLE) as avg
        ")->first();

        $totalCount = (int) ($stats->count ?? 0);
        $globalP95 = null;

        if ($totalCount > 0) {
            $nonSkippedBase = (clone $base)->where('status', '!=', 'skipped');
            $nonSkippedCount = (clone $nonSkippedBase)->count();

            if ($nonSkippedCount > 0) {
                $p95Offset = max(0, (int) ceil($nonSkippedCount * 0.95) - 1);
                $globalP95 = (clone $nonSkippedBase)->orderBy('duration')->skip($p95Offset)->limit(1)->value('duration');
            }
        }

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $slotExpr = $this->bucketSlotExpression($bucketSeconds);
        $bucketMap = $this->fetchBuckets($base, $slotExpr)->keyBy('bucket_slot');

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

        $tasks = $this->fetchTasks($base, $sort, $direction, $search, $page);

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
                'p95' => $globalP95 ? (float) $globalP95 : null,
            ],
        ];
    }

    /**
     * Fetch per-task aggregates grouped by (name, cron).
     *
     * @return array<string, mixed>
     */
    private function fetchTasks(Builder $base, string $sort = 'task', string $direction = 'asc', string $search = '', int $page = 1): array
    {
        if ($search !== '') {
            $base = (clone $base)->where('name', 'like', '%'.$search.'%');
        }

        $allowedSorts = [
            'task' => 'name',
            'processed' => 'processed',
            'skipped' => 'skipped',
            'failed' => 'failed',
            'total' => 'total',
            'avg' => 'avg',
            'p95' => 'p95',
        ];
        $orderCol = $this->resolveSort($sort, $allowedSorts, 'name');
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $aggregates = "
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) AS processed,
            SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) AS skipped,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
            CAST(ROUND(AVG(CASE WHEN status != 'skipped' THEN duration END), 2) AS DOUBLE) AS avg
        ";

        $totalTasks = (clone $base)->selectRaw('COUNT(DISTINCT name) as cnt')->value('cnt') ?? 0;
        $offset = $this->pageOffset($page);

        if (DB::getDriverName() === 'sqlite') {
            $rows = (clone $base)
                ->selectRaw("name, cron, {$aggregates}, NULL AS p95")
                ->groupByRaw('name, cron')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        } else {
            $inner = (clone $base)->select([
                'name',
                'cron',
                'status',
                'duration',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY name, cron ORDER BY duration) AS row_num'),
                DB::raw("SUM(CASE WHEN status != 'skipped' THEN 1 ELSE 0 END) OVER (PARTITION BY name, cron) AS non_skipped_count"),
            ]);

            $rows = DB::query()
                ->fromSub($inner, 'ranked')
                ->selectRaw("name, cron, {$aggregates}, CAST(MAX(CASE WHEN status != 'skipped' AND row_num >= CEIL(0.95 * non_skipped_count) THEN duration END) AS DOUBLE) AS p95")
                ->groupByRaw('name, cron')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        }

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

    /**
     * Driver-aware SQL expression for the integer bucket slot from recorded_at.
     */
    private function bucketSlotExpression(int $bucketSeconds): string
    {
        $epoch = match (DB::getDriverName()) {
            'pgsql' => 'EXTRACT(EPOCH FROM recorded_at)',
            'sqlite' => "CAST(strftime('%s', recorded_at) AS INTEGER)",
            default => 'UNIX_TIMESTAMP(recorded_at)',
        };

        return "FLOOR({$epoch} / {$bucketSeconds})";
    }

    /**
     * Fetch per-bucket aggregates (processed/skipped/failed + avg + p95).
     */
    private function fetchBuckets(Builder $base, string $slotExpr): Collection
    {
        $aggregates = "
            COUNT(*) AS count,
            SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) AS processed,
            SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) AS skipped,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
            CAST(ROUND(AVG(CASE WHEN status != 'skipped' THEN duration END), 2) AS DOUBLE) AS avg
        ";

        if (DB::getDriverName() === 'sqlite') {
            return (clone $base)
                ->selectRaw("{$slotExpr} AS bucket_slot, {$aggregates}, NULL AS p95")
                ->groupByRaw($slotExpr)
                ->orderByRaw($slotExpr)
                ->get();
        }

        $inner = (clone $base)->select([
            'status',
            'duration',
            DB::raw("{$slotExpr} AS bucket_slot"),
            DB::raw("ROW_NUMBER() OVER (PARTITION BY {$slotExpr} ORDER BY duration) AS row_num"),
            DB::raw("COUNT(*) OVER (PARTITION BY {$slotExpr}) AS bucket_count"),
        ]);

        return DB::query()
            ->fromSub($inner, 'ranked')
            ->selectRaw("bucket_slot, {$aggregates}, CAST(MAX(CASE WHEN status != 'skipped' AND row_num >= CEIL(0.95 * bucket_count) THEN duration END) AS DOUBLE) AS p95")
            ->groupBy('bucket_slot')
            ->orderBy('bucket_slot')
            ->get();
    }
}
