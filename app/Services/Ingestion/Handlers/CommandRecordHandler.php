<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\CommandRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class CommandRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_commands';
    }

    public function parse(array $raw): ?CommandRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['name', 'exit_code', 'duration'])) {
            return null;
        }

        return new CommandRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            name: $raw['name'],
            command: ($raw['command'] ?? '') !== '' ? (string) $raw['command'] : null,
            class: $raw['class'] ?? null,
            exitCode: isset($raw['exit_code']) ? (int) $raw['exit_code'] : null,
            duration: isset($raw['duration']) ? (int) $raw['duration'] : null,
            bootstrap: isset($raw['bootstrap']) ? (int) $raw['bootstrap'] : null,
            action: isset($raw['action']) ? (int) $raw['action'] : null,
            terminating: isset($raw['terminating']) ? (int) $raw['terminating'] : null,
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
        /** @var CommandRecordDTO $dto */
        return [
            'name' => $dto->name,
            'command' => $dto->command,
            'class' => $dto->class,
            'exit_code' => $dto->exitCode,
            'duration' => $dto->duration,
            'bootstrap' => $dto->bootstrap,
            'action' => $dto->action,
            'terminating' => $dto->terminating,
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
