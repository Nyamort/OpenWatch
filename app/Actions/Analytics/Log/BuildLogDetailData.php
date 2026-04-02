<?php

namespace App\Actions\Analytics\Log;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildLogDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single log entry.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, string $logId): array
    {
        $orgId = $ctx->organization->id;
        $escapedId = ClickHouseService::escape($logId);

        $log = $this->clickhouse->selectOne("
            SELECT *
            FROM extraction_logs
            WHERE id = {$escapedId}
              AND organization_id = {$orgId}
            LIMIT 1
        ");

        if ($log === null) {
            abort(404, 'Log entry not found.');
        }

        return (new AnalyticsResponseBuilder)
            ->withSummary((array) $log)
            ->build();
    }
}
