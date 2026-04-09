<?php

namespace App\Services\Ingestion\DTOs;

class RequestRecordDTO extends RecordDTO
{
    public function __construct(
        float $timestamp,
        string $deploy,
        string $server,
        public readonly ?string $traceId,
        public readonly ?string $user,
        public readonly ?string $ip,
        public readonly string $method,
        public readonly string $url,
        public readonly ?string $routeName,
        public readonly ?string $routePath,
        public readonly ?string $routeMethods,
        public readonly ?string $routeAction,
        public readonly ?string $routeDomain,
        public readonly int $statusCode,
        public readonly int $duration,
        public readonly ?int $bootstrap,
        public readonly ?int $beforeMiddleware,
        public readonly ?int $action,
        public readonly ?int $render,
        public readonly ?int $afterMiddleware,
        public readonly ?int $terminating,
        public readonly ?int $sending,
        public readonly ?int $requestSize,
        public readonly ?int $responseSize,
        public readonly ?int $peakMemoryUsage,
        public readonly int $exceptions,
        public readonly int $queries,
        public readonly int $logs,
        public readonly int $cacheEvents,
        public readonly int $jobsQueued,
        public readonly int $notifications,
        public readonly int $outgoingRequests,
        public readonly int $lazyLoads,
        public readonly int $hydratedModels,
        public readonly int $filesRead,
        public readonly int $filesWritten,
        public readonly ?string $exceptionPreview,
        public readonly ?string $headers,
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
