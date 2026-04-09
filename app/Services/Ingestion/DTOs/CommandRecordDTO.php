<?php

namespace App\Services\Ingestion\DTOs;

class CommandRecordDTO extends RecordDTO
{
    public function __construct(
        float $timestamp,
        string $deploy,
        string $server,
        public readonly string $name,
        public readonly ?string $command,
        public readonly ?string $class,
        public readonly ?int $exitCode,
        public readonly ?int $duration,
        public readonly ?int $bootstrap,
        public readonly ?int $action,
        public readonly ?int $terminating,
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
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
