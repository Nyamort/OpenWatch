<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\RecordDTO;
use App\Services\Ingestion\DTOs\RequestRecordDTO;

class RequestRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_requests';
    }

    public function parse(array $raw): ?RequestRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['user', 'method', 'url', 'route_name', 'status_code', 'duration', 'ip'])) {
            return null;
        }

        return new RequestRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            traceId: $raw['trace_id'] ?? null,
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            ip: ($raw['ip'] ?? '') !== '' ? (string) $raw['ip'] : null,
            method: $raw['method'],
            url: $raw['url'],
            routeName: $raw['route_name'] ?? null,
            routePath: $raw['route_path'] ?? null,
            routeMethods: is_array($raw['route_methods'] ?? null)
                ? implode('|', $raw['route_methods'])
                : ($raw['route_methods'] ?? null),
            routeAction: $raw['route_action'] ?? null,
            routeDomain: ($raw['route_domain'] ?? '') !== '' ? (string) $raw['route_domain'] : null,
            statusCode: (int) $raw['status_code'],
            duration: (int) $raw['duration'],
            bootstrap: isset($raw['bootstrap']) ? (int) $raw['bootstrap'] : null,
            beforeMiddleware: isset($raw['before_middleware']) ? (int) $raw['before_middleware'] : null,
            action: isset($raw['action']) ? (int) $raw['action'] : null,
            render: isset($raw['render']) ? (int) $raw['render'] : null,
            afterMiddleware: isset($raw['after_middleware']) ? (int) $raw['after_middleware'] : null,
            terminating: isset($raw['terminating']) ? (int) $raw['terminating'] : null,
            sending: isset($raw['sending']) ? (int) $raw['sending'] : null,
            requestSize: isset($raw['request_size']) ? (int) $raw['request_size'] : null,
            responseSize: isset($raw['response_size']) ? (int) $raw['response_size'] : null,
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
            headers: ($raw['headers'] ?? '') !== '' ? (string) $raw['headers'] : null,
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var RequestRecordDTO $dto */
        return [
            'trace_id' => $dto->traceId,
            'user' => $dto->user,
            'ip' => $dto->ip,
            'method' => $dto->method,
            'url' => $dto->url,
            'route_name' => $dto->routeName,
            'route_path' => $dto->routePath,
            'route_methods' => $dto->routeMethods,
            'route_action' => $dto->routeAction,
            'route_domain' => $dto->routeDomain,
            'status_code' => $dto->statusCode,
            'duration' => $dto->duration,
            'bootstrap' => $dto->bootstrap,
            'before_middleware' => $dto->beforeMiddleware,
            'action' => $dto->action,
            'render' => $dto->render,
            'after_middleware' => $dto->afterMiddleware,
            'terminating' => $dto->terminating,
            'sending' => $dto->sending,
            'request_size' => $dto->requestSize,
            'response_size' => $dto->responseSize,
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
            'headers' => $dto->headers,
        ];
    }
}
