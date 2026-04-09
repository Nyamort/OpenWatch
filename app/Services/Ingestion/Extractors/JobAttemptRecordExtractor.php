<?php

namespace App\Services\Ingestion\Extractors;

class JobAttemptRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_job_attempts';
    }

    protected function typeFields(array $record): array
    {
        return [
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
        ];
    }
}
