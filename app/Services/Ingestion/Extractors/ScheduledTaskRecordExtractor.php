<?php

namespace App\Services\Ingestion\Extractors;

class ScheduledTaskRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_scheduled_tasks';
    }

    protected function typeFields(array $record): array
    {
        return [
            'name' => $record['name'],
            'cron' => $this->buildCron($record),
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
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function buildCron(array $record): string
    {
        $cron = $record['cron'];
        $repeatSeconds = (int) ($record['repeat_seconds'] ?? 0);

        if ($repeatSeconds > 0) {
            return '*/'.$repeatSeconds.' '.$cron;
        }

        return $cron;
    }
}
