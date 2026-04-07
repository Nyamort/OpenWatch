<?php

namespace App\Actions\Analytics\OutgoingRequest;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;

class BuildOutgoingRequestHostData
{
    use PaginatesAnalyticsQuery;

    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build individual request list for a given host, ordered by recorded_at desc.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $host): array
    {
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedHost = ClickHouseService::escape($host);

        $total = (int) ($this->clickhouse->selectValue("
            SELECT count()
            FROM extraction_outgoing_requests
            WHERE environment_id = {$envId}
              AND host = {$escapedHost}
              AND recorded_at BETWEEN {$start} AND {$end}
        ") ?? 0);

        $rows = $this->clickhouse->select("
            SELECT *
            FROM extraction_outgoing_requests
            WHERE environment_id = {$envId}
              AND host = {$escapedHost}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at DESC
            LIMIT 50
        ");

        return (new AnalyticsResponseBuilder)
            ->withSummary([
                'host' => $host,
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
