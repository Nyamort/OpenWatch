<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class AnonymizeStaleAuditEvents implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $retentionDays = (int) config('observability.audit_retention_days', 365);
        $cutoff = now()->subDays($retentionDays);

        DB::table('organization_audit_events')
            ->where('created_at', '<', $cutoff)
            ->where(function ($query): void {
                $query->whereNotNull('actor_id')
                    ->orWhereNotNull('ip')
                    ->orWhere('user_agent', '!=', 'anonymized');
            })
            ->update([
                'actor_id' => null,
                'ip' => 'anonymized',
                'user_agent' => 'anonymized',
            ]);
    }
}
