<?php

namespace App\Services\Ingestion\DTOs;

class JobAttemptRecordDTO extends RecordDTO
{
    public function __construct(
        float $timestamp,
        string $deploy,
        string $server,
        public readonly ?string $user,
        public readonly string $jobId,
        public readonly string $attemptId,
        public readonly int $attempt,
        public readonly string $name,
        public readonly string $connection,
        public readonly string $queue,
        public readonly string $status,
        public readonly ?int $duration,
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
