<?php

namespace App\Jobs;

use App\Models\TelemetryRecord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class PurgeExpiredTelemetryRecords implements ShouldQueue
{
    use Queueable;

    private const EXTRACTION_TABLES = [
        'extraction_requests',
        'extraction_queries',
        'extraction_cache_events',
        'extraction_commands',
        'extraction_logs',
        'extraction_notifications',
        'extraction_mails',
        'extraction_queued_jobs',
        'extraction_job_attempts',
        'extraction_scheduled_tasks',
        'extraction_outgoing_requests',
        'extraction_exceptions',
        'extraction_user_activities',
    ];

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $retentionDays = (int) config('observability.telemetry_retention_days', 90);
        $cutoff = now()->subDays($retentionDays);

        $expiredIds = TelemetryRecord::query()
            ->where('recorded_at', '<', $cutoff)
            ->pluck('id');

        if ($expiredIds->isEmpty()) {
            return;
        }

        foreach (self::EXTRACTION_TABLES as $table) {
            DB::table($table)
                ->whereIn('telemetry_record_id', $expiredIds)
                ->delete();
        }

        TelemetryRecord::query()
            ->whereIn('id', $expiredIds)
            ->delete();
    }
}
