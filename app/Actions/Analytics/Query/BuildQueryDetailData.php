<?php

namespace App\Actions\Analytics\Query;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;

class BuildQueryDetailData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build all occurrences for a given sql_hash, ordered newest-first, paginated.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sqlHash): array
    {
        $orgId = $ctx->organization->id;
        $projId = $ctx->project->id;
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedHash = ClickHouseService::escape($sqlHash);

        $baseWhere = "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND sql_hash = {$escapedHash}
            AND recorded_at BETWEEN {$start} AND {$end}";

        $summary = $this->clickhouse->selectOne("
            SELECT
                any(sql_normalized) AS sql_preview,
                count() AS total,
                toUInt32(if(isFinite(avg(duration)), round(avg(duration) / 1000.0), 0)) AS avg_duration_ms,
                toUInt32(if(isFinite(quantile(0.95)(duration)), round(quantile(0.95)(duration) / 1000.0), 0)) AS p95_duration_ms,
                toUInt32(if(isFinite(max(duration)), round(max(duration) / 1000.0), 0)) AS max_duration_ms
            FROM extraction_queries
            {$baseWhere}
        ");

        $total = (int) ($summary?->total ?? 0);
        $page = 1;
        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT *
            FROM extraction_queries
            {$baseWhere}
            ORDER BY recorded_at DESC
            LIMIT 50 OFFSET {$offset}
        ");

        return (new AnalyticsResponseBuilder)
            ->withSummary([
                'sql_hash' => $sqlHash,
                'sql_preview' => $summary?->sql_preview,
                'total' => $total,
                'avg_duration_ms' => $summary?->avg_duration_ms ?? 0,
                'p95_duration_ms' => $summary?->p95_duration_ms ?? 0,
                'max_duration_ms' => $summary?->max_duration_ms ?? 0,
                'period_label' => $period->label,
            ])
            ->withRows($rows->toArray())
            ->withPagination([
                'current_page' => 1,
                'last_page' => (int) ceil($total / 50),
                'per_page' => 50,
                'total' => $total,
            ])
            ->build();
    }
}
