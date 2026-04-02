<?php

namespace App\Jobs;

use App\Models\Environment;
use App\Services\ClickHouse\ClickHouseService;
use App\Services\Ingestion\RecordValidatorRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
    public function handle(RecordValidatorRegistry $registry, ClickHouseService $clickhouse): void
    {
        $environment = Environment::with('project.organization')->find($this->environmentId);

        if ($environment === null) {
            return;
        }

        $organizationId = $environment->project->organization->id;
        $projectId = $environment->project->id;

        $telemetryRows = [];
        $extractionRows = [];

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
                $recordedAt = \Carbon\Carbon::createFromTimestamp($record['timestamp'])->utc()->format('Y-m-d H:i:s');
                $telemetryRecordId = Str::uuid()->toString();

                $telemetryRows[] = [
                    'id' => $telemetryRecordId,
                    'organization_id' => $organizationId,
                    'project_id' => $projectId,
                    'environment_id' => $this->environmentId,
                    'record_type' => $type,
                    'trace_id' => $traceId,
                    'group_key' => $groupKey,
                    'execution_id' => $executionId,
                    'payload' => json_encode($record),
                    'recorded_at' => $recordedAt,
                ];

                $extractionRow = $this->buildExtractionRow(
                    $type,
                    $telemetryRecordId,
                    $organizationId,
                    $projectId,
                    $record,
                    $recordedAt,
                );

                if ($extractionRow !== null) {
                    $extractionRows[$this->extractionTable($type)][] = $extractionRow;
                }
            } catch (InvalidArgumentException $e) {
                report($e);

                continue;
            }
        }

        if (! empty($telemetryRows)) {
            $clickhouse->insert('telemetry_records', $telemetryRows);
        }

        foreach ($extractionRows as $table => $rows) {
            $clickhouse->insert($table, $rows);
        }
    }

    /**
     * Build the row for the type-specific extraction table.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function buildExtractionRow(
        string $type,
        string $telemetryRecordId,
        int $organizationId,
        int $projectId,
        array $record,
        string $recordedAt,
    ): ?array {
        $base = [
            'id' => Str::uuid()->toString(),
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
                'status_code' => (int) $record['status_code'],
                'duration' => (int) $record['duration'],
                'request_size' => isset($record['request_size']) ? (int) $record['request_size'] : null,
                'response_size' => isset($record['response_size']) ? (int) $record['response_size'] : null,
                'peak_memory_usage' => isset($record['peak_memory_usage']) ? (int) $record['peak_memory_usage'] : null,
                'exceptions' => (int) ($record['exceptions'] ?? 0),
                'queries' => (int) ($record['queries'] ?? 0),
            ],
            'query' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'user' => $record['user'] ?? null,
                'sql_hash' => hash('sha256', $record['sql']),
                'sql_normalized' => $record['sql'],
                'connection' => $record['connection'],
                'connection_type' => $record['connection_type'],
                'duration' => (int) $record['duration'],
            ],
            'cache-event' => [
                'store' => $record['store'],
                'key' => $record['key'],
                'type' => $record['type'],
                'duration' => (int) $record['duration'],
                'ttl' => isset($record['ttl']) ? (int) $record['ttl'] : null,
            ],
            'command' => [
                'name' => $record['name'],
                'class' => $record['class'] ?? null,
                'exit_code' => isset($record['exit_code']) ? (int) $record['exit_code'] : null,
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
            ],
            'log' => [
                'level' => $record['level'],
                'message' => $record['message'],
                'execution_id' => $record['execution_id'] ?? null,
            ],
            'notification' => [
                'channel' => $record['channel'],
                'class' => $record['class'],
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
                'failed' => (int) ($record['failed'] ?? 0),
            ],
            'mail' => [
                'mailer' => $record['mailer'],
                'class' => $record['class'],
                'subject' => $record['subject'],
                'to' => json_encode($record['to'] ?? null),
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
                'failed' => (int) ($record['failed'] ?? 0),
            ],
            'queued-job' => [
                'job_id' => $record['job_id'],
                'name' => $record['name'],
                'connection' => $record['connection'],
                'queue' => $record['queue'],
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
            ],
            'job-attempt' => [
                'job_id' => $record['job_id'],
                'attempt_id' => $record['attempt_id'],
                'attempt' => (int) $record['attempt'],
                'name' => $record['name'],
                'connection' => $record['connection'] ?? '',
                'queue' => $record['queue'] ?? '',
                'status' => $record['status'],
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
            ],
            'scheduled-task' => [
                'name' => $record['name'],
                'cron' => $this->buildScheduledTaskCron($record),
                'status' => $record['status'],
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
            ],
            'outgoing-request' => [
                'host' => $record['host'],
                'method' => $record['method'],
                'url' => $record['url'],
                'status_code' => isset($record['status_code']) ? (int) $record['status_code'] : null,
                'duration' => (int) $record['duration'],
            ],
            'exception' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'group_key' => $record['_group'] ?? null,
                'user' => $record['user'] ?? null,
                'class' => $record['class'],
                'file' => $record['file'] ?? null,
                'line' => isset($record['line']) ? (int) $record['line'] : null,
                'message' => $record['message'],
                'handled' => (int) ($record['handled'] ?? 0),
                'php_version' => $record['php_version'] ?? null,
                'laravel_version' => $record['laravel_version'] ?? null,
            ],
            'user' => [],
            default => null,
        };

        if ($typeFields === null) {
            return null;
        }

        return array_merge($base, $typeFields);
    }

    /**
     * Build the cron expression for a scheduled task record.
     *
     * @param  array<string, mixed>  $record
     */
    private function buildScheduledTaskCron(array $record): string
    {
        $cron = $record['cron'];
        $repeatSeconds = (int) ($record['repeat_seconds'] ?? 0);

        if ($repeatSeconds > 0) {
            return '*/'.$repeatSeconds.' '.$cron;
        }

        return $cron;
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
