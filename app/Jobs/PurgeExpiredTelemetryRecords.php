<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PurgeExpiredTelemetryRecords implements ShouldQueue
{
    use Queueable;

    /**
     * Telemetry retention is managed by ClickHouse TTL rules defined at table creation.
     * This job is kept as a no-op so existing schedules are not broken.
     * To change the retention window, update CLICKHOUSE_TELEMETRY_RETENTION_DAYS and
     * re-run `php artisan clickhouse:migrate` (ALTER TABLE ... MODIFY TTL is also supported).
     */
    public function handle(): void {}
}
