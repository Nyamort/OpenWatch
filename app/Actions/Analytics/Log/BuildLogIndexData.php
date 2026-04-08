<?php

namespace App\Actions\Analytics\Log;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;
use Carbon\Carbon;

class BuildLogIndexData
{
    use PaginatesAnalyticsQuery;

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
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);

        $baseWhere = "WHERE environment_id = {$envId}
            AND recorded_at BETWEEN {$start} AND {$end}";

        if ($level !== null && in_array($level, self::LEVELS, true)) {
            $escapedLevel = ClickHouseService::escape($level);
            $baseWhere .= " AND level = {$escapedLevel}";
        }

        if ($search !== null && $search !== '') {
            $escaped = ClickHouseService::escape('%'.$search.'%');
            $baseWhere .= " AND message LIKE {$escaped}";
        }

        $total = (int) ($this->clickhouse->selectValue("
            SELECT count() FROM extraction_logs {$baseWhere}
        ") ?? 0);

        $offset = $this->pageOffset($page);

        $rows = $this->clickhouse->select("
            SELECT id, recorded_at, execution_source, execution_preview, level, message, context
            FROM extraction_logs
            {$baseWhere}
            ORDER BY recorded_at DESC
            LIMIT {$this->analyticsPerPage} OFFSET {$offset}
        ");

        $logs = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => Carbon::parse($row->recorded_at)->format('Y-m-d H:i:s'),
            'source' => $row->execution_source ?: null,
            'source_preview' => $row->execution_preview ?: null,
            'level' => $row->level,
            'message' => $row->message,
            'context' => $row->context ?: null,
        ])->all();

        return [
            'logs' => $logs,
            'pagination' => $this->buildPaginationMeta($total, $page),
        ];
    }
}
