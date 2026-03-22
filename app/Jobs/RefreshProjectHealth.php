<?php

namespace App\Jobs;

use App\Models\Environment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshProjectHealth implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * Updates environment health_status based on last_ingested_at signals.
     */
    public function handle(): void
    {
        $healthyThreshold = now()->subMinutes(10);
        $warningThreshold = now()->subHour();

        // Mark healthy environments that have ingested recently
        Environment::query()
            ->whereNull('archived_at')
            ->where('last_ingested_at', '>=', $healthyThreshold)
            ->update(['health_status' => 'healthy']);

        // Mark degraded environments that haven't ingested recently
        Environment::query()
            ->whereNull('archived_at')
            ->where('last_ingested_at', '<', $healthyThreshold)
            ->where('last_ingested_at', '>=', $warningThreshold)
            ->update(['health_status' => 'degraded']);

        // Mark inactive environments that have never or very long ago ingested
        Environment::query()
            ->whereNull('archived_at')
            ->where(function ($query) use ($warningThreshold): void {
                $query->whereNull('last_ingested_at')
                    ->orWhere('last_ingested_at', '<', $warningThreshold);
            })
            ->update(['health_status' => 'inactive']);
    }
}
