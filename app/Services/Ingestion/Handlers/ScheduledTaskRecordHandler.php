<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\RecordDTO;
use App\Services\Ingestion\DTOs\ScheduledTaskRecordDTO;

class ScheduledTaskRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_scheduled_tasks';
    }

    public function parse(array $raw): ?ScheduledTaskRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['name', 'cron', 'status', 'duration'])) {
            return null;
        }

        $cron = $raw['cron'];
        $repeatSeconds = (int) ($raw['repeat_seconds'] ?? 0);

        if ($repeatSeconds > 0) {
            $cron = '*/'.$repeatSeconds.' '.$cron;
        }

        return new ScheduledTaskRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            name: $raw['name'],
            cron: $cron,
            timezone: (string) ($raw['timezone'] ?? 'UTC'),
            status: $raw['status'],
            duration: isset($raw['duration']) ? (int) $raw['duration'] : null,
            peakMemoryUsage: isset($raw['peak_memory_usage']) ? (int) $raw['peak_memory_usage'] : null,
            withoutOverlapping: (int) ($raw['without_overlapping'] ?? 0),
            onOneServer: (int) ($raw['on_one_server'] ?? 0),
            runInBackground: (int) ($raw['run_in_background'] ?? 0),
            evenInMaintenanceMode: (int) ($raw['even_in_maintenance_mode'] ?? 0),
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
        /** @var ScheduledTaskRecordDTO $dto */
        return [
            'name' => $dto->name,
            'cron' => $dto->cron,
            'timezone' => $dto->timezone,
            'status' => $dto->status,
            'duration' => $dto->duration,
            'peak_memory_usage' => $dto->peakMemoryUsage,
            'without_overlapping' => $dto->withoutOverlapping,
            'on_one_server' => $dto->onOneServer,
            'run_in_background' => $dto->runInBackground,
            'even_in_maintenance_mode' => $dto->evenInMaintenanceMode,
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
