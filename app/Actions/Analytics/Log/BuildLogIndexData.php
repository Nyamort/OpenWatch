<?php

namespace App\Actions\Analytics\Log;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;

class BuildLogIndexData
{
    /** @var list<string> RFC 5424 log levels in severity order */
    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build a paginated log feed ordered newest-first with optional filters.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        ?string $level = null,
        ?string $search = null,
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

        if ($level !== null && in_array($level, self::LEVELS, true)) {
            $escapedLevel = ClickHouseService::escape($level);
            $baseWhere .= " AND level = {$escapedLevel}";
        }

        if ($search !== null && $search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $baseWhere .= " AND message LIKE {$escaped}";
        }

        $perPage = 100;
        $total = (int) ($this->clickhouse->selectValue("
            SELECT count() FROM extraction_logs {$baseWhere}
        ") ?? 0);

        $offset = ($page - 1) * $perPage;

        $rows = $this->clickhouse->select("
            SELECT *
            FROM extraction_logs
            {$baseWhere}
            ORDER BY recorded_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");

        return (new AnalyticsResponseBuilder)
            ->withSummary(['period_label' => $period->label])
            ->withRows($rows->toArray())
            ->withPagination([
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ])
            ->withFiltersApplied([
                'level' => $level,
                'search' => $search,
            ])
            ->withConfig([
                'levels' => self::LEVELS,
                'period' => $period->label,
            ])
            ->build();
    }
}
