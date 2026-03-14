<?php

namespace App\Actions\Analytics\Log;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use Illuminate\Support\Facades\DB;

class BuildLogDetailData
{
    /**
     * Fetch a single log entry with full payload from telemetry_records.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, int $logId): array
    {
        $log = DB::table('extraction_logs')
            ->where('id', $logId)
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->first();

        if ($log === null) {
            abort(404, 'Log entry not found.');
        }

        $telemetryRecord = DB::table('telemetry_records')
            ->where('id', $log->telemetry_record_id)
            ->first();

        $payload = $telemetryRecord?->payload ? json_decode($telemetryRecord->payload, true) : null;

        return (new AnalyticsResponseBuilder)
            ->withSummary(array_merge((array) $log, ['payload' => $payload]))
            ->build();
    }
}
