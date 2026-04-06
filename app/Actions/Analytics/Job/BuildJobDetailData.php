<?php

namespace App\Actions\Analytics\Job;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildJobDetailData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets, stats and paginated attempts for a single job class.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $name,
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

        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND name = {$escapedName}
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

        $allowedSorts = ['date' => 'recorded_at', 'status' => 'status', 'duration' => 'duration'];
        $orderCol = $allowedSorts[$sort] ?? 'recorded_at';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';
        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT id, recorded_at, connection, queue, status, attempt, duration
            FROM extraction_job_attempts
            {$baseWhere}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => Carbon::parse($row->recorded_at)->format('Y-m-d H:i:s'),
            'connection' => $row->connection,
            'queue' => $row->queue,
            'status' => $row->status,
            'attempt' => $row->attempt,
            'duration' => $row->duration,
        ])->all();

        return [
            'graph' => $graph,
            'attempts' => $data,
            'pagination' => $this->buildPaginationMeta($totalCount, $page),
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
}
