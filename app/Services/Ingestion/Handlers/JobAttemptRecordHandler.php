<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\JobAttemptRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class JobAttemptRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_job_attempts';
    }

    public function parse(array $raw): ?JobAttemptRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['job_id', 'attempt_id', 'attempt', 'name', 'status'])) {
            return null;
        }

        return new JobAttemptRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            jobId: $raw['job_id'],
            attemptId: $raw['attempt_id'],
            attempt: (int) $raw['attempt'],
            name: $raw['name'],
            connection: $raw['connection'] ?? '',
            queue: $raw['queue'] ?? '',
            status: $raw['status'],
            duration: isset($raw['duration']) ? (int) $raw['duration'] : null,
            peakMemoryUsage: isset($raw['peak_memory_usage']) ? (int) $raw['peak_memory_usage'] : null,
            exceptions: (int) ($raw['exceptions'] ?? 0),
            queries: (int) ($raw['queries'] ?? 0),
            logs: (int) ($raw['logs'] ?? 0),
            cacheEvents: (int) ($raw['cache_events'] ?? 0),
            jobsQueued: (int) ($raw['jobs_queued'] ?? 0),
            notifications: (int) ($raw['notifications'] ?? 0),
            outgoingRequests: (int) ($raw['outgoing_requests'] ?? 0),
            lazyLoads: (int) ($raw['lazy_loads'] ?? 0),
            hydratedModels: (int) ($raw['hydrated_models'] ?? 0),
            filesRead: (int) ($raw['files_read'] ?? 0),
            filesWritten: (int) ($raw['files_written'] ?? 0),
            exceptionPreview: ($raw['exception_preview'] ?? '') !== '' ? (string) $raw['exception_preview'] : null,
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var JobAttemptRecordDTO $dto */
        return [
            'user' => $dto->user,
            'job_id' => $dto->jobId,
            'attempt_id' => $dto->attemptId,
            'attempt' => $dto->attempt,
            'name' => $dto->name,
            'connection' => $dto->connection,
            'queue' => $dto->queue,
            'status' => $dto->status,
            'duration' => $dto->duration,
            'peak_memory_usage' => $dto->peakMemoryUsage,
            'exceptions' => $dto->exceptions,
            'queries' => $dto->queries,
            'logs' => $dto->logs,
            'cache_events' => $dto->cacheEvents,
            'jobs_queued' => $dto->jobsQueued,
            'notifications' => $dto->notifications,
            'outgoing_requests' => $dto->outgoingRequests,
            'lazy_loads' => $dto->lazyLoads,
            'hydrated_models' => $dto->hydratedModels,
            'files_read' => $dto->filesRead,
            'files_written' => $dto->filesWritten,
            'exception_preview' => $dto->exceptionPreview,
        ];
    }
}
