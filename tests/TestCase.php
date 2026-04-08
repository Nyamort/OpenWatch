<?php

namespace Tests;

use App\Services\ClickHouse\ClickHouseService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents(storage_path('app/.installed'), now()->toIso8601String());

        try {
            $clickhouse = app(ClickHouseService::class);
            $tables = [
                'extraction_requests', 'extraction_exceptions', 'extraction_queries',
                'extraction_logs', 'extraction_cache_events', 'extraction_commands',
                'extraction_notifications', 'extraction_mails', 'extraction_queued_jobs',
                'extraction_job_attempts', 'extraction_scheduled_tasks',
                'extraction_outgoing_requests', 'extraction_user_activities',
            ];

            foreach ($tables as $table) {
                $clickhouse->statement("TRUNCATE TABLE {$table}");
            }
        } catch (\Throwable) {
            // ClickHouse may not be available in all test environments
        }
    }
}
