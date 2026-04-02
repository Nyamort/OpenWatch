<?php

namespace App\Actions\Analytics\Log;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildLogDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single log entry with the full raw payload from telemetry_records.
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

        $telemetryRecordId = ClickHouseService::escape($log->telemetry_record_id ?? '');

        $telemetryRecord = $this->clickhouse->selectOne("
            SELECT payload
            FROM telemetry_records
            WHERE id = {$telemetryRecordId}
            LIMIT 1
        ");

        $payload = $telemetryRecord?->payload ? json_decode($telemetryRecord->payload, true) : null;

        return (new AnalyticsResponseBuilder)
            ->withSummary(array_merge((array) $log, ['payload' => $payload]))
            ->build();
    }
}
