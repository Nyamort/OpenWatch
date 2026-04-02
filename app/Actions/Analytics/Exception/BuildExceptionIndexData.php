<?php

namespace App\Actions\Analytics\Exception;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildExceptionIndexData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets, global stats, and paginated exception table.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $sort = 'last_seen',
        string $direction = 'desc',
        string $search = '',
        int $page = 1,
    ): array {
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
                count() AS total,
                sum(handled) AS handled_count,
                sum(1 - handled) AS unhandled_count
            FROM extraction_exceptions
            {$baseWhere}
        ");

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                sum(handled) AS handled_count,
                sum(1 - handled) AS unhandled_count
            FROM extraction_exceptions
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
                'handled' => (int) ($row?->handled_count ?? 0),
                'unhandled' => (int) ($row?->unhandled_count ?? 0),
            ];
        }

        $exceptions = $this->fetchExceptions($orgId, $projId, $envId, $start, $end, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'stats' => [
                'count' => (int) ($stats?->total ?? 0),
                'handled' => (int) ($stats?->handled_count ?? 0),
                'unhandled' => (int) ($stats?->unhandled_count ?? 0),
            ],
            'exceptions' => $exceptions['data'],
            'pagination' => $exceptions['pagination'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchExceptions(int $orgId, int $projId, int $envId, string $start, string $end, string $sort, string $direction, string $search, int $page): array
    {
        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        if ($search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $baseWhere .= " AND class LIKE {$escaped}";
        }

        $allowedSorts = [
            'last_seen' => 'last_seen',
            'class' => 'class',
            'count' => 'count',
            'users' => 'users',
        ];
        $orderCol = $allowedSorts[$sort] ?? 'last_seen';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';

        $totalGroups = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(group_key) FROM extraction_exceptions {$baseWhere}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                group_key,
                any(class) AS class,
                count() AS count,
                uniqExact(user) AS users,
                max(recorded_at) AS last_seen,
                min(recorded_at) AS first_seen
            FROM extraction_exceptions
            {$baseWhere}
            GROUP BY group_key
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'group_key' => $row->group_key,
            'class' => $row->class,
            'count' => (int) $row->count,
            'users' => (int) $row->users,
            'last_seen' => $row->last_seen,
            'first_seen' => $row->first_seen,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalGroups, $page),
        ];
    }
}
