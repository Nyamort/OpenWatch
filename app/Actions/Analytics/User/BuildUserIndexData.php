<?php

namespace App\Actions\Analytics\User;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildUserIndexData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build graph buckets, global stats, and per-user rows for user analytics.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sort = 'request_count', string $direction = 'desc', string $search = '', int $page = 1): array
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

        $stats = $this->clickhouse->selectOne("
            SELECT
                uniqExactIf(user, user != '') AS authenticated_users,
                countIf(user != '') AS authenticated_requests,
                countIf(isNull(user) OR user = '') AS guest_requests
            FROM extraction_requests
            {$baseWhere}
        ");

        $bucketSeconds = $period->bucketSeconds;
        $bucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                uniqExactIf(user, user != '') AS authenticated_users,
                countIf(user != '') AS authenticated,
                countIf(isNull(user) OR user = '') AS guest
            FROM extraction_requests
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
                'authenticated_users' => (int) ($row?->authenticated_users ?? 0),
                'authenticated' => (int) ($row?->authenticated ?? 0),
                'guest' => (int) ($row?->guest ?? 0),
            ];
        }

        $users = $this->fetchUsers($orgId, $projId, $envId, $start, $end, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'stats' => [
                'authenticated_users' => (int) ($stats?->authenticated_users ?? 0),
                'authenticated_requests' => (int) ($stats?->authenticated_requests ?? 0),
                'guest_requests' => (int) ($stats?->guest_requests ?? 0),
            ],
            'users' => $users['data'],
            'pagination' => $users['pagination'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchUsers(int $orgId, int $projId, int $envId, string $start, string $end, string $sort, string $direction, string $search, int $page): array
    {
        $baseConditions = "organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        $userFilter = "r.user != ''";
        $countUserFilter = "user != ''";

        if ($search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $userFilter .= " AND r.user LIKE {$escaped}";
            $countUserFilter .= " AND user LIKE {$escaped}";
        }

        $allowedSorts = [
            'user' => 'user',
            '2xx' => '`2xx`',
            '4xx' => '`4xx`',
            '5xx' => '`5xx`',
            'request_count' => 'request_count',
            'job_count' => 'job_count',
            'exception_count' => 'exception_count',
            'last_seen' => 'last_seen',
        ];
        $orderCol = $allowedSorts[$sort] ?? 'request_count';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';

        $totalUsers = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(user)
            FROM extraction_requests
            WHERE {$baseConditions} AND {$countUserFilter}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                r.user AS user,
                countIf(r.status_code < 400) AS `2xx`,
                countIf(r.status_code >= 400 AND r.status_code < 500) AS `4xx`,
                countIf(r.status_code >= 500) AS `5xx`,
                count() AS request_count,
                coalesce(any(j.job_count), 0) AS job_count,
                coalesce(any(e.exception_count), 0) AS exception_count,
                max(r.recorded_at) AS last_seen
            FROM extraction_requests AS r
            LEFT JOIN (
                SELECT user, count() AS job_count
                FROM extraction_queued_jobs
                WHERE {$baseConditions} AND user != ''
                GROUP BY user
            ) AS j ON r.user = j.user
            LEFT JOIN (
                SELECT user, count() AS exception_count
                FROM extraction_exceptions
                WHERE {$baseConditions} AND user != ''
                GROUP BY user
            ) AS e ON r.user = e.user
            WHERE {$baseConditions} AND {$userFilter}
            GROUP BY r.user
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'user' => $row->user,
            '2xx' => (int) ($row->{'2xx'} ?? 0),
            '4xx' => (int) ($row->{'4xx'} ?? 0),
            '5xx' => (int) ($row->{'5xx'} ?? 0),
            'request_count' => (int) $row->request_count,
            'job_count' => (int) ($row->job_count ?? 0),
            'exception_count' => (int) ($row->exception_count ?? 0),
            'last_seen' => $row->last_seen,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalUsers, $page),
        ];
    }
}
