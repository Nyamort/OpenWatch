<?php

namespace App\Actions\Analytics\Job;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildJobsIndexData
{
    use PaginatesAnalyticsQuery;

    /**
     * Build graph buckets and global stats for job analytics.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sort = 'total', string $direction = 'desc', string $search = '', int $page = 1): array
    {
        $base = DB::table('extraction_job_attempts')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        // Global stats
        $stats = (clone $base)->selectRaw("
            COUNT(*) as count,
            SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) as released,
            CAST(MIN(duration) AS DOUBLE) as min,
            CAST(MAX(duration) AS DOUBLE) as max,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) as avg
        ")->first();

        $totalCount = (int) ($stats->count ?? 0);
        $globalP95 = null;

        if ($totalCount > 0) {
            $p95Offset = max(0, (int) ceil($totalCount * 0.95) - 1);
            $globalP95 = (clone $base)->orderBy('duration')->skip($p95Offset)->limit(1)->value('duration');
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
                'count' => (int) ($row?->count ?? 0),
                'processed' => (int) ($row?->processed ?? 0),
                'failed' => (int) ($row?->failed ?? 0),
                'released' => (int) ($row?->released ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $queuedBase = DB::table('extraction_queued_jobs')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        $jobs = $this->fetchJobs($base, $queuedBase, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'jobs' => $jobs['data'],
            'pagination' => $jobs['pagination'],
            'stats' => [
                'count' => $totalCount,
                'processed' => (int) ($stats?->processed ?? 0),
                'failed' => (int) ($stats?->failed ?? 0),
                'released' => (int) ($stats?->released ?? 0),
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'avg' => $stats->avg ?? null,
                'p95' => $globalP95 ? (float) $globalP95 : null,
            ],
        ];
    }

    /**
     * Fetch per-job aggregates grouped by name.
     *
     * @return array<string, mixed>
     */
    private function fetchJobs(Builder $base, Builder $queuedBase, string $sort = 'total', string $direction = 'desc', string $search = '', int $page = 1): array
    {
        if ($search !== '') {
            $base = (clone $base)->where('name', 'like', '%'.$search.'%');
            $queuedBase = (clone $queuedBase)->where('name', 'like', '%'.$search.'%');
        }

        $allowedSorts = ['name' => 'name', 'total' => 'total', 'queued' => 'queued', 'processed' => 'processed', 'failed' => 'failed', 'released' => 'released', 'avg' => 'avg', 'p95' => 'p95'];
        $orderCol = $this->resolveSort($sort, $allowedSorts, 'total');
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $aggregates = "
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) AS processed,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
            SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) AS released,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg
        ";

        $queuedSub = (clone $queuedBase)
            ->select(['name', DB::raw('COUNT(*) AS queued')])
            ->groupBy('name');

        $totalJobs = (clone $base)->distinct()->count('name');
        $offset = $this->pageOffset($page);

        if (DB::getDriverName() === 'sqlite') {
            $rows = (clone $base)
                ->leftJoinSub($queuedSub, 'q', 'extraction_job_attempts.name', '=', 'q.name')
                ->selectRaw("extraction_job_attempts.name, {$aggregates}, COALESCE(q.queued, 0) AS queued, NULL AS p95")
                ->groupByRaw('extraction_job_attempts.name')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        } else {
            $inner = (clone $base)->select([
                'name',
                'status',
                'duration',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY name ORDER BY duration) AS row_num'),
                DB::raw('COUNT(*) OVER (PARTITION BY name) AS name_count'),
            ]);

            $rows = DB::query()
                ->fromSub($inner, 'ranked')
                ->leftJoinSub($queuedSub, 'q', 'ranked.name', '=', 'q.name')
                ->selectRaw("ranked.name, {$aggregates}, COALESCE(q.queued, 0) AS queued, CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * name_count) THEN duration END) AS DOUBLE) AS p95")
                ->groupByRaw('ranked.name')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        }

        $data = $rows->map(fn ($row) => [
            'name' => $row->name ?: null,
            'total' => (int) $row->total,
            'queued' => (int) ($row->queued ?? 0),
            'processed' => (int) ($row->processed ?? 0),
            'failed' => (int) ($row->failed ?? 0),
            'released' => (int) ($row->released ?? 0),
            'avg' => $row->avg,
            'p95' => $row->p95,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalJobs, $page),
        ];
    }

    /**
     * Driver-aware SQL expression for the integer bucket slot from recorded_at.
     */
    private function bucketSlotExpression(int $bucketSeconds): string
    {
        $epoch = match (DB::getDriverName()) {
            'pgsql' => 'EXTRACT(EPOCH FROM recorded_at)',
            'sqlite' => 'CAST(strftime(\'%s\', recorded_at) AS INTEGER)',
            default => 'UNIX_TIMESTAMP(recorded_at)',
        };

        return "FLOOR({$epoch} / {$bucketSeconds})";
    }

    /**
     * Fetch per-bucket aggregates.
     * Uses window functions for p95 on MySQL/PostgreSQL; returns NULL on SQLite.
     */
    private function fetchBuckets(Builder $base, string $slotExpr): Collection
    {
        $aggregates = "
            COUNT(*) AS count,
            SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) AS processed,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
            SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) AS released,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg
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
            ->selectRaw("bucket_slot, {$aggregates}, CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * bucket_count) THEN duration END) AS DOUBLE) AS p95")
            ->groupBy('bucket_slot')
            ->orderBy('bucket_slot')
            ->get();
    }
}
