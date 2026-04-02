<?php

namespace App\Jobs;

use App\Models\Environment;
use App\Services\ClickHouse\ClickHouseService;
use App\Services\Ingestion\RecordValidatorRegistry;
use Carbon\Carbon;
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

        $extractionRows = [];

        foreach ($this->records as $record) {
            try {
                if (! $registry->validate($record)) {
                    Log::info('Invalid telemetry record', ['record' => $record]);

                    continue;
                }

                $type = $record['t'];
                $recordedAt = Carbon::createFromTimestamp($record['timestamp'])->utc()->format('Y-m-d H:i:s');
                $telemetryRecordId = Str::uuid()->toString();

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
            'deploy' => (string) ($record['deploy'] ?? ''),
            'server' => (string) ($record['server'] ?? ''),
            'recorded_at' => $recordedAt,
        ];

        $typeFields = match ($type) {
            'request' => [
                'trace_id' => $record['trace_id'] ?? null,
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'ip' => ($record['ip'] ?? '') !== '' ? (string) $record['ip'] : null,
                'method' => $record['method'],
                'url' => $record['url'],
                'route_name' => $record['route_name'] ?? null,
                'route_path' => $record['route_path'] ?? null,
                'route_methods' => is_array($record['route_methods'] ?? null)
                    ? implode('|', $record['route_methods'])
                    : ($record['route_methods'] ?? null),
                'route_action' => $record['route_action'] ?? null,
                'route_domain' => ($record['route_domain'] ?? '') !== '' ? (string) $record['route_domain'] : null,
                'status_code' => (int) $record['status_code'],
                'duration' => (int) $record['duration'],
                'bootstrap' => isset($record['bootstrap']) ? (int) $record['bootstrap'] : null,
                'before_middleware' => isset($record['before_middleware']) ? (int) $record['before_middleware'] : null,
                'action' => isset($record['action']) ? (int) $record['action'] : null,
                'render' => isset($record['render']) ? (int) $record['render'] : null,
                'after_middleware' => isset($record['after_middleware']) ? (int) $record['after_middleware'] : null,
                'terminating' => isset($record['terminating']) ? (int) $record['terminating'] : null,
                'sending' => isset($record['sending']) ? (int) $record['sending'] : null,
                'request_size' => isset($record['request_size']) ? (int) $record['request_size'] : null,
                'response_size' => isset($record['response_size']) ? (int) $record['response_size'] : null,
                'peak_memory_usage' => isset($record['peak_memory_usage']) ? (int) $record['peak_memory_usage'] : null,
                'exceptions' => (int) ($record['exceptions'] ?? 0),
                'queries' => (int) ($record['queries'] ?? 0),
                'logs' => (int) ($record['logs'] ?? 0),
                'cache_events' => (int) ($record['cache_events'] ?? 0),
                'jobs_queued' => (int) ($record['jobs_queued'] ?? 0),
                'notifications' => (int) ($record['notifications'] ?? 0),
                'outgoing_requests' => (int) ($record['outgoing_requests'] ?? 0),
                'lazy_loads' => (int) ($record['lazy_loads'] ?? 0),
                'hydrated_models' => (int) ($record['hydrated_models'] ?? 0),
                'files_read' => (int) ($record['files_read'] ?? 0),
                'files_written' => (int) ($record['files_written'] ?? 0),
                'exception_preview' => ($record['exception_preview'] ?? '') !== '' ? (string) $record['exception_preview'] : null,
            ],
            'exception' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'execution_source' => (string) ($record['execution_source'] ?? ''),
                'execution_stage' => (string) ($record['execution_stage'] ?? ''),
                'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
                'group_key' => $record['_group'] ?? null,
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'class' => $record['class'],
                'file' => $record['file'] ?? null,
                'line' => isset($record['line']) ? (int) $record['line'] : null,
                'message' => $record['message'],
                'code' => isset($record['code']) ? (string) $record['code'] : null,
                'trace' => is_array($record['trace'] ?? null) ? json_encode($record['trace']) : (string) ($record['trace'] ?? ''),
                'handled' => (int) ($record['handled'] ?? 0),
                'php_version' => $record['php_version'] ?? null,
                'laravel_version' => $record['laravel_version'] ?? null,
            ],
            'query' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'execution_source' => (string) ($record['execution_source'] ?? ''),
                'execution_stage' => (string) ($record['execution_stage'] ?? ''),
                'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'sql_hash' => hash('sha256', $record['sql']),
                'sql_normalized' => $record['sql'],
                'file' => ($record['file'] ?? '') !== '' ? (string) $record['file'] : null,
                'line' => isset($record['line']) && $record['line'] > 0 ? (int) $record['line'] : null,
                'connection' => $record['connection'],
                'connection_type' => $record['connection_type'],
                'duration' => (int) $record['duration'],
            ],
            'log' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'execution_source' => (string) ($record['execution_source'] ?? ''),
                'execution_stage' => (string) ($record['execution_stage'] ?? ''),
                'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'level' => $record['level'],
                'message' => $record['message'],
                'context' => is_array($record['context'] ?? null) ? json_encode($record['context']) : (string) ($record['context'] ?? '{}'),
                'extra' => is_array($record['extra'] ?? null) ? json_encode($record['extra']) : (string) ($record['extra'] ?? '{}'),
            ],
            'cache-event' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'execution_source' => (string) ($record['execution_source'] ?? ''),
                'execution_stage' => (string) ($record['execution_stage'] ?? ''),
                'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'store' => $record['store'],
                'key' => $record['key'],
                'type' => $record['type'],
                'duration' => (int) $record['duration'],
                'ttl' => isset($record['ttl']) ? (int) $record['ttl'] : null,
            ],
            'command' => [
                'name' => $record['name'],
                'command' => ($record['command'] ?? '') !== '' ? (string) $record['command'] : null,
                'class' => $record['class'] ?? null,
                'exit_code' => isset($record['exit_code']) ? (int) $record['exit_code'] : null,
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
                'bootstrap' => isset($record['bootstrap']) ? (int) $record['bootstrap'] : null,
                'action' => isset($record['action']) ? (int) $record['action'] : null,
                'terminating' => isset($record['terminating']) ? (int) $record['terminating'] : null,
                'peak_memory_usage' => isset($record['peak_memory_usage']) ? (int) $record['peak_memory_usage'] : null,
                'exceptions' => (int) ($record['exceptions'] ?? 0),
                'queries' => (int) ($record['queries'] ?? 0),
                'logs' => (int) ($record['logs'] ?? 0),
                'cache_events' => (int) ($record['cache_events'] ?? 0),
                'jobs_queued' => (int) ($record['jobs_queued'] ?? 0),
                'notifications' => (int) ($record['notifications'] ?? 0),
                'outgoing_requests' => (int) ($record['outgoing_requests'] ?? 0),
                'lazy_loads' => (int) ($record['lazy_loads'] ?? 0),
                'hydrated_models' => (int) ($record['hydrated_models'] ?? 0),
                'files_read' => (int) ($record['files_read'] ?? 0),
                'files_written' => (int) ($record['files_written'] ?? 0),
                'exception_preview' => ($record['exception_preview'] ?? '') !== '' ? (string) $record['exception_preview'] : null,
            ],
            'notification' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'execution_source' => (string) ($record['execution_source'] ?? ''),
                'execution_stage' => (string) ($record['execution_stage'] ?? ''),
                'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'channel' => $record['channel'],
                'class' => $record['class'],
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
                'failed' => (int) ($record['failed'] ?? 0),
            ],
            'mail' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'execution_source' => (string) ($record['execution_source'] ?? ''),
                'execution_stage' => (string) ($record['execution_stage'] ?? ''),
                'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'mailer' => $record['mailer'],
                'class' => $record['class'],
                'subject' => $record['subject'],
                'to' => (int) ($record['to'] ?? 0),
                'cc' => (int) ($record['cc'] ?? 0),
                'bcc' => (int) ($record['bcc'] ?? 0),
                'attachments' => (int) ($record['attachments'] ?? 0),
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
                'failed' => (int) ($record['failed'] ?? 0),
            ],
            'queued-job' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'execution_source' => (string) ($record['execution_source'] ?? ''),
                'execution_stage' => (string) ($record['execution_stage'] ?? ''),
                'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'job_id' => $record['job_id'],
                'name' => $record['name'],
                'connection' => $record['connection'],
                'queue' => $record['queue'],
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
            ],
            'job-attempt' => [
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'job_id' => $record['job_id'],
                'attempt_id' => $record['attempt_id'],
                'attempt' => (int) $record['attempt'],
                'name' => $record['name'],
                'connection' => $record['connection'] ?? '',
                'queue' => $record['queue'] ?? '',
                'status' => $record['status'],
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
                'peak_memory_usage' => isset($record['peak_memory_usage']) ? (int) $record['peak_memory_usage'] : null,
                'exceptions' => (int) ($record['exceptions'] ?? 0),
                'queries' => (int) ($record['queries'] ?? 0),
                'logs' => (int) ($record['logs'] ?? 0),
                'cache_events' => (int) ($record['cache_events'] ?? 0),
                'jobs_queued' => (int) ($record['jobs_queued'] ?? 0),
                'notifications' => (int) ($record['notifications'] ?? 0),
                'outgoing_requests' => (int) ($record['outgoing_requests'] ?? 0),
                'lazy_loads' => (int) ($record['lazy_loads'] ?? 0),
                'hydrated_models' => (int) ($record['hydrated_models'] ?? 0),
                'files_read' => (int) ($record['files_read'] ?? 0),
                'files_written' => (int) ($record['files_written'] ?? 0),
                'exception_preview' => ($record['exception_preview'] ?? '') !== '' ? (string) $record['exception_preview'] : null,
            ],
            'scheduled-task' => [
                'name' => $record['name'],
                'cron' => $this->buildScheduledTaskCron($record),
                'timezone' => (string) ($record['timezone'] ?? 'UTC'),
                'status' => $record['status'],
                'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
                'peak_memory_usage' => isset($record['peak_memory_usage']) ? (int) $record['peak_memory_usage'] : null,
                'without_overlapping' => (int) ($record['without_overlapping'] ?? 0),
                'on_one_server' => (int) ($record['on_one_server'] ?? 0),
                'run_in_background' => (int) ($record['run_in_background'] ?? 0),
                'even_in_maintenance_mode' => (int) ($record['even_in_maintenance_mode'] ?? 0),
                'exceptions' => (int) ($record['exceptions'] ?? 0),
                'queries' => (int) ($record['queries'] ?? 0),
                'logs' => (int) ($record['logs'] ?? 0),
                'cache_events' => (int) ($record['cache_events'] ?? 0),
                'jobs_queued' => (int) ($record['jobs_queued'] ?? 0),
                'notifications' => (int) ($record['notifications'] ?? 0),
                'outgoing_requests' => (int) ($record['outgoing_requests'] ?? 0),
                'lazy_loads' => (int) ($record['lazy_loads'] ?? 0),
                'hydrated_models' => (int) ($record['hydrated_models'] ?? 0),
                'files_read' => (int) ($record['files_read'] ?? 0),
                'files_written' => (int) ($record['files_written'] ?? 0),
                'exception_preview' => ($record['exception_preview'] ?? '') !== '' ? (string) $record['exception_preview'] : null,
            ],
            'outgoing-request' => [
                'trace_id' => $record['trace_id'] ?? null,
                'execution_id' => $record['execution_id'] ?? null,
                'execution_source' => (string) ($record['execution_source'] ?? ''),
                'execution_stage' => (string) ($record['execution_stage'] ?? ''),
                'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
                'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
                'host' => $record['host'],
                'method' => $record['method'],
                'url' => $record['url'],
                'status_code' => isset($record['status_code']) ? (int) $record['status_code'] : null,
                'duration' => (int) $record['duration'],
                'request_size' => isset($record['request_size']) ? (int) $record['request_size'] : null,
                'response_size' => isset($record['response_size']) ? (int) $record['response_size'] : null,
            ],
            'user' => [
                'user_id' => ($record['id'] ?? '') !== '' ? (string) $record['id'] : null,
                'name' => ($record['name'] ?? '') !== '' ? (string) $record['name'] : null,
                'username' => ($record['username'] ?? '') !== '' ? (string) $record['username'] : null,
            ],
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
