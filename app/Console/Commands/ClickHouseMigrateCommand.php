<?php

namespace App\Console\Commands;

use App\Services\ClickHouse\ClickHouseService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ClickHouseMigrateCommand extends Command
{
    protected $signature = 'clickhouse:migrate
                            {--fresh : Drop all tables and re-run every migration}
                            {--status : Show migration status without running anything}';

    protected $description = 'Run pending ClickHouse migrations';

    private const MIGRATIONS_TABLE = 'clickhouse_migrations';

    private const MIGRATIONS_PATH = 'clickhouse/migrations';

    public function handle(ClickHouseService $clickhouse): int
    {
        if ($this->option('fresh')) {
            return $this->fresh($clickhouse);
        }

        $this->ensureMigrationsTableExists($clickhouse);

        $pending = $this->pendingMigrations($clickhouse);

        if ($this->option('status')) {
            return $this->showStatus($clickhouse, $pending);
        }

        if ($pending->isEmpty()) {
            $this->info('Nothing to migrate.');

            return self::SUCCESS;
        }

        foreach ($pending as $file) {
            $this->runMigration($clickhouse, $file);
        }

        $this->info('ClickHouse migrations complete.');

        return self::SUCCESS;
    }

    private function fresh(ClickHouseService $clickhouse): int
    {
        if (! $this->confirm('This will drop all ClickHouse tables. Continue?')) {
            return self::FAILURE;
        }

        $tables = $clickhouse->select('SHOW TABLES');

        foreach ($tables as $row) {
            $table = array_values((array) $row)[0];
            $this->line("Dropping <comment>{$table}</comment>");
            $clickhouse->statement("DROP TABLE IF EXISTS {$table}");
        }

        $this->ensureMigrationsTableExists($clickhouse);

        foreach ($this->migrationFiles() as $file) {
            $this->runMigration($clickhouse, $file);
        }

        $this->info('ClickHouse refreshed successfully.');

        return self::SUCCESS;
    }

    private function showStatus(ClickHouseService $clickhouse, Collection $pending): int
    {
        $applied = $this->appliedMigrations($clickhouse);
        $all = $this->migrationFiles();

        $rows = $all->map(fn (string $file) => [
            $applied->contains(basename($file)) ? '<info>Applied</info>' : '<comment>Pending</comment>',
            basename($file),
        ]);

        $this->table(['Status', 'Migration'], $rows->all());

        return self::SUCCESS;
    }

    private function runMigration(ClickHouseService $clickhouse, string $file): void
    {
        $name = basename($file);
        $this->line("Migrating: <comment>{$name}</comment>");

        $sql = file_get_contents($file);
        $sql = $this->substitutePlaceholders($sql);

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn (string $s) => $s !== '',
        );

        foreach ($statements as $statement) {
            $clickhouse->statement($statement);
        }

        $clickhouse->insert(self::MIGRATIONS_TABLE, [
            ['migration' => $name, 'applied_at' => now()->utc()->format('Y-m-d H:i:s')],
        ]);

        $this->line("Migrated:  <info>{$name}</info>");
    }

    private function ensureMigrationsTableExists(ClickHouseService $clickhouse): void
    {
        $clickhouse->statement('
            CREATE TABLE IF NOT EXISTS '.self::MIGRATIONS_TABLE.'
            (
                migration  String,
                applied_at DateTime(\'UTC\') DEFAULT now()
            )
            ENGINE = MergeTree()
            ORDER BY migration
        ');
    }

    private function pendingMigrations(ClickHouseService $clickhouse): Collection
    {
        $applied = $this->appliedMigrations($clickhouse);

        return $this->migrationFiles()
            ->reject(fn (string $file) => $applied->contains(basename($file)));
    }

    private function appliedMigrations(ClickHouseService $clickhouse): Collection
    {
        return $clickhouse->select('SELECT migration FROM '.self::MIGRATIONS_TABLE)
            ->pluck('migration');
    }

    private function migrationFiles(): Collection
    {
        $path = database_path(self::MIGRATIONS_PATH);

        return collect(glob("{$path}/*.sql"))->sort()->values();
    }

    private function substitutePlaceholders(string $sql): string
    {
        $retentionDays = config('clickhouse.telemetry_retention_days', 30);

        return str_replace('{telemetry_retention_days:UInt32}', (string) $retentionDays, $sql);
    }
}
