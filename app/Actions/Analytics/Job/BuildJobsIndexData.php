<?php

namespace App\Actions\Analytics\Job;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildJobsIndexData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets and global stats for job analytics.
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
                countIf(status = 'processed') AS processed,
                countIf(status = 'failed') AS failed,
                countIf(status = 'released') AS released,
                toUInt32(min(duration)) AS min,
                toUInt32(max(duration)) AS max,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_job_attempts
            {$baseWhere}
        ");

        $totalCount = (int) ($stats->count ?? 0);

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                count() AS count,
                countIf(status = 'processed') AS processed,
                countIf(status = 'failed') AS failed,
                countIf(status = 'released') AS released,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration)), 0)) AS p95
            FROM extraction_job_attempts
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
                'processed' => (int) ($row?->processed ?? 0),
                'failed' => (int) ($row?->failed ?? 0),
                'released' => (int) ($row?->released ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $jobs = $this->fetchJobs($orgId, $projId, $envId, $start, $end, $sort, $direction, $search, $page);

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
                'p95' => $stats->p95 ?? null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchJobs(int $orgId, int $projId, int $envId, string $start, string $end, string $sort, string $direction, string $search, int $page): array
    {
        $attemptsWhere = "WHERE a.organization_id = {$orgId}
            AND a.project_id = {$projId}
            AND a.environment_id = {$envId}
            AND a.recorded_at BETWEEN {$start} AND {$end}";

        $queuedWhere = "organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        if ($search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $attemptsWhere .= " AND a.name LIKE {$escaped}";
            $queuedWhere .= " AND name LIKE {$escaped}";
        }

        $allowedSorts = ['name' => 'a.name', 'total' => 'total', 'queued' => 'queued', 'processed' => 'processed', 'failed' => 'failed', 'released' => 'released', 'avg' => 'avg', 'p95' => 'p95'];
        $orderCol = $allowedSorts[$sort] ?? 'total';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';

        $totalJobs = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(name) FROM extraction_job_attempts
            WHERE organization_id = {$orgId} AND project_id = {$projId} AND environment_id = {$envId}
              AND recorded_at BETWEEN {$start} AND {$end}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                a.name,
                count() AS total,
                countIf(a.status = 'processed') AS processed,
                countIf(a.status = 'failed') AS failed,
                countIf(a.status = 'released') AS released,
                toUInt32(if(isFinite(avg(a.duration)), round(avg(a.duration)), 0)) AS avg,
                toUInt32(if(isFinite(quantile(0.95)(a.duration)), round(quantile(0.95)(a.duration)), 0)) AS p95,
                coalesce(any(q.queued), 0) AS queued
            FROM extraction_job_attempts AS a
            LEFT JOIN (
                SELECT name, count() AS queued
                FROM extraction_queued_jobs
                WHERE {$queuedWhere}
                GROUP BY name
            ) AS q ON a.name = q.name
            {$attemptsWhere}
            GROUP BY a.name
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

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
}
