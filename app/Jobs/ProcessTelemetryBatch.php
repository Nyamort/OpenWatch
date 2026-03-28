<?php

namespace App\Jobs;

use App\Models\Environment;
use App\Models\TelemetryRecord;
use App\Services\Ingestion\RecordValidatorRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProcessTelemetryBatch implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<int, array<string, mixed>>  $records
     */
    public function __construct(
        public readonly int $environmentId,
        public readonly array $records,
        public readonly string $requestId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RecordValidatorRegistry $registry): void
    {
        $environment = Environment::with('project.organization')->find($this->environmentId);

        if ($environment === null) {
            return;
        }

        $organizationId = $environment->project->organization->id;
        $projectId = $environment->project->id;

        foreach ($this->records as $record) {
            try {
                if (! $registry->validate($record)) {
                    Log::info('Invalid telemetry record', ['record' => $record]);

                    continue;
                }

                $type = $record['t'];
                $traceId = $record['trace_id'] ?? null;
                $groupKey = $record['_group'] ?? null;
                $executionId = $record['execution_id'] ?? null;
                $recordedAt = \Carbon\Carbon::createFromTimestamp($record['timestamp'])->toDateTimeString();

                $telemetryRecord = TelemetryRecord::create([
                    'organization_id' => $organizationId,
                    'project_id' => $projectId,
                    'environment_id' => $this->environmentId,
                    'record_type' => $type,
                    'trace_id' => $traceId,
                    'group_key' => $groupKey,
                    'execution_id' => $executionId,
                    'payload' => $record,
                    'recorded_at' => $recordedAt,
                ]);

                $this->insertExtractionRecord($type, $telemetryRecord->id, $organizationId, $projectId, $record, $recordedAt);
            } catch (InvalidArgumentException) {
                // Unknown type — skip
                continue;
            }
        }
    }

    /**
     * Insert into the type-specific extraction table.
     *
     * @param  array<string, mixed>  $record
     */
    private function insertExtractionRecord(
        string $type,
        int $telemetryRecordId,
        int $organizationId,
        int $projectId,
        array $record,
        mixed $recordedAt,
    ): void {
        $table = $this->extractionTable($type);

        $base = [
            'telemetry_record_id' => $telemetryRecordId,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'environment_id' => $this->environmentId,
            'recorded_at' => $recordedAt,
        ];

        $typeFields = match ($type) {
            'request' => [
                'trace_id' => $record['trace_id'] ?? null,
                'user' => $record['user'] ?? null,
                'method' => $record['method'],
                'url' => $record['url'],
                'route_name' => $record['route_name'] ?? null,
                'route_path' => $record['route_path'] ?? null,
                'route_methods' => is_array($record['route_methods'] ?? null)
                    ? implode('|', $record['route_methods'])
                    : ($record['route_methods'] ?? null),
                'route_action' => $record['route_action'] ?? null,
                'status_code' => $record['status_code'],
                'duration' => $record['duration'],
                'request_size' => $record['request_size'] ?? null,
                'response_size' => $record['response_size'] ?? null,
                'peak_memory_usage' => $record['peak_memory_usage'] ?? null,
                'exceptions' => $record['exceptions'] ?? 0,
                'queries' => $record['queries'] ?? 0,
            ],
            'query' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'user' => $record['user'] ?? null,
                'sql_hash' => hash('sha256', $record['sql']),
                'sql_normalized' => $record['sql'],
                'connection' => $record['connection'],
                'connection_type' => $record['connection_type'],
                'duration' => $record['duration'],
            ],
            'cache-event' => [
                'store' => $record['store'],
                'key' => $record['key'],
                'type' => $record['type'],
                'duration' => $record['duration'],
                'ttl' => $record['ttl'] ?? null,
            ],
            'command' => [
                'name' => $record['name'],
                'class' => $record['class'] ?? null,
                'exit_code' => $record['exit_code'] ?? null,
                'duration' => $record['duration'] ?? null,
                'status' => $record['status'] ?? 'pending',
            ],
            'log' => [
                'level' => $record['level'],
                'message' => $record['message'],
                'execution_id' => $record['execution_id'] ?? null,
            ],
            'notification' => [
                'channel' => $record['channel'],
                'class' => $record['class'],
                'duration' => $record['duration'] ?? null,
                'failed' => $record['failed'] ?? false,
            ],
            'mail' => [
                'mailer' => $record['mailer'],
                'class' => $record['class'],
                'subject' => $record['subject'],
                'to' => json_encode($record['to'] ?? null),
                'duration' => $record['duration'] ?? null,
                'failed' => $record['failed'] ?? false,
            ],
            'queued-job' => [
                'job_id' => $record['job_id'],
                'name' => $record['name'],
                'connection' => $record['connection'],
                'queue' => $record['queue'],
                'duration' => $record['duration'] ?? null,
            ],
            'job-attempt' => [
                'job_id' => $record['job_id'],
                'attempt_id' => $record['attempt_id'],
                'attempt' => $record['attempt'],
                'name' => $record['name'],
                'connection' => $record['connection'] ?? '',
                'queue' => $record['queue'] ?? '',
                'status' => $record['status'],
                'duration' => $record['duration'] ?? null,
            ],
            'scheduled-task' => [
                'name' => $record['name'],
                'cron' => $record['cron'],
                'status' => $record['status'],
                'duration' => $record['duration'] ?? null,
            ],
            'outgoing-request' => [
                'host' => $record['host'],
                'method' => $record['method'],
                'url' => $record['url'],
                'status_code' => $record['status_code'] ?? null,
                'duration' => $record['duration'],
            ],
            'exception' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'group_key' => $record['_group'] ?? null,
                'user' => $record['user'] ?? null,
                'class' => $record['class'],
                'file' => $record['file'] ?? null,
                'line' => $record['line'] ?? null,
                'message' => $record['message'],
                'handled' => $record['handled'] ?? false,
                'php_version' => $record['php_version'] ?? null,
                'laravel_version' => $record['laravel_version'] ?? null,
            ],
            'user' => [],
            default => [],
        };

        DB::table($table)->insert(array_merge($base, $typeFields));
    }

    /**
     * Resolve the extraction table name for the given record type.
     */
    private function extractionTable(string $type): string
    {
        return match ($type) {
            'request' => 'extraction_requests',
            'query' => 'extraction_queries',
            'cache-event' => 'extraction_cache_events',
            'command' => 'extraction_commands',
            'log' => 'extraction_logs',
            'notification' => 'extraction_notifications',
            'mail' => 'extraction_mails',
            'queued-job' => 'extraction_queued_jobs',
            'job-attempt' => 'extraction_job_attempts',
            'scheduled-task' => 'extraction_scheduled_tasks',
            'outgoing-request' => 'extraction_outgoing_requests',
            'exception' => 'extraction_exceptions',
            'user' => 'extraction_user_activities',
        };
    }
}
