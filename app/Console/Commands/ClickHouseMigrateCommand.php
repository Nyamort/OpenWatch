<?php

namespace App\Console\Commands;

use App\Services\ClickHouse\ClickHouseService;
use Illuminate\Console\Command;

class ClickHouseMigrateCommand extends Command
{
    protected $signature = 'clickhouse:migrate';

    protected $description = 'Create ClickHouse tables from database/clickhouse/schema.sql';

    public function handle(ClickHouseService $clickhouse): int
    {
        $retentionDays = config('clickhouse.telemetry_retention_days', 30);
        $schemaPath = database_path('clickhouse/schema.sql');

        if (! file_exists($schemaPath)) {
            $this->error("Schema file not found: {$schemaPath}");

            return self::FAILURE;
        }

        $sql = file_get_contents($schemaPath);

        // Substitute the retention days placeholder
        $sql = str_replace('{telemetry_retention_days:UInt32}', (string) $retentionDays, $sql);

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn (string $s) => $s !== '',
        );

        foreach ($statements as $statement) {
            $this->line('Running: '.substr($statement, 0, 60).'...');
            $clickhouse->statement($statement);
        }

        $this->info('ClickHouse tables created successfully.');

        return self::SUCCESS;
    }
}
