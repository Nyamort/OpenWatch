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
     * Users are sourced from extraction_user_activities (username = email).
     * Stats (requests, jobs, exceptions) are joined from the other tables.
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

        // Global stats: authenticated users from user_activities, requests split from extraction_requests
        $userStats = $this->clickhouse->selectOne("
            SELECT uniqExact(username) AS authenticated_users
            FROM extraction_user_activities
            {$baseWhere} AND username != ''
        ");

        $requestStats = $this->clickhouse->selectOne("
            SELECT
                countIf(user != '') AS authenticated_requests,
                countIf(isNull(user) OR user = '') AS guest_requests
            FROM extraction_requests
            {$baseWhere}
        ");

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;

        $userBucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                uniqExact(username) AS authenticated_users
            FROM extraction_user_activities
            {$baseWhere} AND username != ''
            GROUP BY bucket_slot
        ")->keyBy('bucket_slot');

        $requestBucketMap = $this->clickhouse->select("
            SELECT
                intDiv(toUnixTimestamp(recorded_at), {$bucketSeconds}) AS bucket_slot,
                countIf(user != '') AS authenticated,
                countIf(isNull(user) OR user = '') AS guest
            FROM extraction_requests
            {$baseWhere}
            GROUP BY bucket_slot
        ")->keyBy('bucket_slot');

        $graph = [];
        $startSlot = (int) floor(Carbon::parse($period->start)->utc()->timestamp / $bucketSeconds);
        $endSlot = (int) floor(Carbon::parse($period->end)->utc()->timestamp / $bucketSeconds);

        for ($slot = $startSlot; $slot <= $endSlot; $slot++) {
            $uRow = $userBucketMap->get($slot);
            $rRow = $requestBucketMap->get($slot);
            $graph[] = [
                'bucket' => Carbon::createFromTimestampUTC($slot * $bucketSeconds)->format('Y-m-d H:i:s'),
                'authenticated_users' => (int) ($uRow?->authenticated_users ?? 0),
                'authenticated' => (int) ($rRow?->authenticated ?? 0),
                'guest' => (int) ($rRow?->guest ?? 0),
            ];
        }

        $users = $this->fetchUsers($orgId, $projId, $envId, $start, $end, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'stats' => [
                'authenticated_users' => (int) ($userStats?->authenticated_users ?? 0),
                'authenticated_requests' => (int) ($requestStats?->authenticated_requests ?? 0),
                'guest_requests' => (int) ($requestStats?->guest_requests ?? 0),
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

        $emailFilter = "username != ''";
        if ($search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $emailFilter .= " AND username LIKE {$escaped}";
        }

        $allowedSorts = [
            'email' => 'email',
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
            SELECT uniqExact(username)
            FROM extraction_user_activities
            WHERE {$baseConditions} AND {$emailFilter}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                ua.username AS email,
                ua.name,
                coalesce(r.`2xx`, 0) AS `2xx`,
                coalesce(r.`4xx`, 0) AS `4xx`,
                coalesce(r.`5xx`, 0) AS `5xx`,
                coalesce(r.request_count, 0) AS request_count,
                coalesce(j.job_count, 0) AS job_count,
                coalesce(e.exception_count, 0) AS exception_count,
                ua.last_activity AS last_seen
            FROM (
                SELECT username, any(name) AS name, max(recorded_at) AS last_activity
                FROM extraction_user_activities
                WHERE {$baseConditions} AND {$emailFilter}
                GROUP BY username
            ) AS ua
            LEFT JOIN (
                SELECT
                    user,
                    count() AS request_count,
                    countIf(status_code < 400) AS `2xx`,
                    countIf(status_code >= 400 AND status_code < 500) AS `4xx`,
                    countIf(status_code >= 500) AS `5xx`
                FROM extraction_requests
                WHERE {$baseConditions} AND user != ''
                GROUP BY user
            ) AS r ON ua.username = r.user
            LEFT JOIN (
                SELECT user, count() AS job_count
                FROM extraction_queued_jobs
                WHERE {$baseConditions} AND user != ''
                GROUP BY user
            ) AS j ON ua.username = j.user
            LEFT JOIN (
                SELECT user, count() AS exception_count
                FROM extraction_exceptions
                WHERE {$baseConditions} AND user != ''
                GROUP BY user
            ) AS e ON ua.username = e.user
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'email' => $row->email,
            'name' => $row->name ?: null,
            '2xx' => (int) ($row->{'2xx'} ?? 0),
            '4xx' => (int) ($row->{'4xx'} ?? 0),
            '5xx' => (int) ($row->{'5xx'} ?? 0),
            'request_count' => (int) ($row->request_count ?? 0),
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
