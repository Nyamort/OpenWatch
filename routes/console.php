<?php

use App\Actions\Observability\RefreshOrganizationDashboardSnapshot;
use App\Actions\Project\RecalculateProjectHealth;
use App\Jobs\EvaluateAlertRules;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\RawTelemetryRecord;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('observability:prune {--telemetry-days=} {--audit-days=}', function (): void {
    $telemetryDaysOption = $this->option('telemetry-days');
    $auditDaysOption = $this->option('audit-days');

    $telemetryRetentionDays = max((int) ($telemetryDaysOption ?? config('observability.telemetry_retention_days', 30)), 1);
    $auditRetentionDays = max((int) ($auditDaysOption ?? config('observability.audit_retention_days', 90)), 1);

    $prunedTelemetryCount = RawTelemetryRecord::query()
        ->where('ts_utc', '<', now()->subDays($telemetryRetentionDays))
        ->delete();

    $prunedAuditCount = AuditLog::query()
        ->where('created_at', '<', now()->subDays($auditRetentionDays))
        ->delete();

    $this->info("Pruned telemetry records: {$prunedTelemetryCount}");
    $this->info("Pruned audit logs: {$prunedAuditCount}");
})->purpose('Prune observability tables based on retention policies');

Artisan::command('observability:refresh-dashboard-snapshots {organization?*}', function (RefreshOrganizationDashboardSnapshot $refreshDashboardSnapshot): void {
    /** @var array<int, string> $organizationSlugs */
    $organizationSlugs = $this->argument('organization');

    $organizationsQuery = Organization::query();

    if ($organizationSlugs !== []) {
        $organizationsQuery->whereIn('slug', $organizationSlugs);
    }

    $processedCount = 0;

    $organizationsQuery->each(function (Organization $organization) use ($refreshDashboardSnapshot, &$processedCount): void {
        $refreshDashboardSnapshot($organization);
        $processedCount++;
    });

    $this->info("Dashboard snapshots refreshed: {$processedCount}");
})->purpose('Refresh dashboard snapshots for organizations');

Artisan::command('observability:anonymize-audit {--days=} {--limit=1000}', function (): void {
    $retentionDays = max((int) ($this->option('days') ?? config('observability.audit_retention_days', 90)), 1);
    $limit = max((int) $this->option('limit'), 1);

    $updatedCount = 0;

    AuditLog::query()
        ->where('created_at', '<', now()->subDays($retentionDays))
        ->where(function ($query): void {
            $query->whereNotNull('ip_address')
                ->orWhereNotNull('user_agent');
        })
        ->limit($limit)
        ->eachById(function (AuditLog $log) use (&$updatedCount): void {
            $log->forceFill([
                'ip_address' => null,
                'user_agent' => null,
            ])->save();

            $updatedCount++;
        });

    $this->info("Anonymized audit logs: {$updatedCount}");
})->purpose('Anonymize stale audit records while preserving event integrity');

Artisan::command('observability:prepare-partitions {--months=2}', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->info('Skipped: partition preparation is only applicable for PostgreSQL.');

        return;
    }

    $months = max((int) $this->option('months'), 1);
    $now = now()->startOfMonth();

    for ($index = 0; $index < $months; $index++) {
        $start = $now->copy()->addMonths($index);
        $end = $start->copy()->addMonth();
        $partitionName = sprintf('raw_telemetry_records_%s', $start->format('Ym'));

        DB::statement(sprintf(
            "CREATE TABLE IF NOT EXISTS %s PARTITION OF raw_telemetry_records FOR VALUES FROM ('%s') TO ('%s')",
            $partitionName,
            $start->toDateString(),
            $end->toDateString(),
        ));
    }

    $this->info('PostgreSQL partitions prepared.');
})->purpose('Prepare monthly PostgreSQL partitions for raw telemetry');

Artisan::command('projects:refresh-health {organization?*}', function (RecalculateProjectHealth $recalculateProjectHealth): void {
    /** @var array<int, string> $organizationSlugs */
    $organizationSlugs = $this->argument('organization');

    $projectsQuery = Project::query()
        ->whereNull('projects.deleted_at')
        ->whereNull('projects.archived_at');

    if ($organizationSlugs !== []) {
        $projectsQuery->whereHas('organization', function ($builder) use ($organizationSlugs): void {
            $builder->whereIn('slug', $organizationSlugs);
        });
    }

    $processedCount = 0;

    $projectsQuery->each(function (Project $project) use ($recalculateProjectHealth, &$processedCount): void {
        $recalculateProjectHealth($project);
        $processedCount++;
    });

    $this->info("Project health refreshed: {$processedCount}");
})->purpose('Recalculate project health from environment signals');

Schedule::command('observability:prune')->dailyAt('02:00');
Schedule::command('observability:anonymize-audit')->dailyAt('02:10');
Schedule::command('observability:refresh-dashboard-snapshots')->everyFiveMinutes();
Schedule::command('projects:refresh-health')->everyMinute();
Schedule::job(new EvaluateAlertRules)->everyMinute();
