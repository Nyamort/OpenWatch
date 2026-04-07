<?php

namespace App\Actions\Analytics\Exception;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use App\Services\ClickHouse\ClickHouseService;

class BuildExceptionDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Resolve group_key to representative record (latest) and all occurrences paginated.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $groupKey): array
    {
        $envId = $ctx->environment->id;
        $start = ClickHouseService::escape($period->start);
        $end = ClickHouseService::escape($period->end);
        $escapedKey = ClickHouseService::escape($groupKey);

        $representative = $this->clickhouse->selectOne("
            SELECT *
            FROM extraction_exceptions
            WHERE environment_id = {$envId}
              AND group_key = {$escapedKey}
            ORDER BY recorded_at DESC
            LIMIT 1
        ");

        if ($representative === null) {
            abort(404, 'Exception group not found.');
        }

        $total = (int) ($this->clickhouse->selectValue("
            SELECT count()
            FROM extraction_exceptions
            WHERE environment_id = {$envId}
              AND group_key = {$escapedKey}
              AND recorded_at BETWEEN {$start} AND {$end}
        ") ?? 0);

        $occurrences = $this->clickhouse->select("
            SELECT *
            FROM extraction_exceptions
            WHERE environment_id = {$envId}
              AND group_key = {$escapedKey}
              AND recorded_at BETWEEN {$start} AND {$end}
            ORDER BY recorded_at DESC
            LIMIT 50
        ");

        $traceId = ClickHouseService::escape($representative->trace_id ?? '');

        $relatedRequests = $this->clickhouse->select("
            SELECT id, route_path, method, status_code, recorded_at
            FROM extraction_requests
            WHERE environment_id = {$envId}
              AND trace_id = {$traceId}
        ")->toArray();

        return (new AnalyticsResponseBuilder)
            ->withSummary(array_merge((array) $representative, [
                'related_requests' => $relatedRequests,
            ]))
            ->withRows($occurrences->toArray())
            ->withPagination([
                'current_page' => 1,
                'last_page' => (int) ceil($total / 50),
                'per_page' => 50,
                'total' => $total,
            ])
            ->build();
    }
}
