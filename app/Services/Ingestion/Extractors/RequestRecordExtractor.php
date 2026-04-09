<?php

namespace App\Services\Ingestion\Extractors;

class RequestRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_requests';
    }

    protected function typeFields(array $record): array
    {
        return [
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
            'headers' => ($record['headers'] ?? '') !== '' ? (string) $record['headers'] : null,
        ];
    }
}
