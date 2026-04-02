<?php

namespace App\Actions\Analytics\User;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;

class BuildUserIndexData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build user analytics by aggregating across requests and exceptions.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sort = 'request_count', string $direction = 'desc', int $page = 1): array
    {
        $orgId = $ctx->organization->id;
        $projId = $ctx->project->id;
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);

        $baseConditions = "organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}
            AND user != ''";

        $allowedSorts = ['request_count', 'exception_count'];
        $orderCol = in_array($sort, $allowedSorts) ? $sort : 'request_count';
        $orderDir = $direction === 'asc' ? 'ASC' : 'DESC';

        $totalUsers = (int) ($this->clickhouse->selectValue("
            SELECT uniqExact(user) FROM (
                SELECT user FROM extraction_requests WHERE {$baseConditions}
                UNION ALL
                SELECT user FROM extraction_exceptions WHERE {$baseConditions}
            )
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT
                user,
                countIf(is_request = 1) AS request_count,
                countIf(is_request = 0) AS exception_count
            FROM (
                SELECT user, 1 AS is_request FROM extraction_requests WHERE {$baseConditions}
                UNION ALL
                SELECT user, 0 AS is_request FROM extraction_exceptions WHERE {$baseConditions}
            )
            GROUP BY user
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $data = $rows->map(fn ($row) => [
            'user' => $row->user,
            'request_count' => (int) $row->request_count,
            'exception_count' => (int) $row->exception_count,
        ])->all();

        return [
            'users' => $data,
            'pagination' => $this->buildPaginationMeta($totalUsers, $page),
            'summary' => ['period_label' => $period->label],
        ];
    }
}
