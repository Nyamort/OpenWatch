<?php

namespace App\Services\Ingestion\DTOs;

class QueuedJobRecordDTO extends RecordDTO
{
    public function __construct(
        float $timestamp,
        string $deploy,
        string $server,
        public readonly ?string $traceId,
        public readonly ?string $executionId,
        public readonly string $executionSource,
        public readonly string $executionStage,
        public readonly ?string $executionPreview,
        public readonly ?string $user,
        public readonly string $jobId,
        public readonly string $name,
        public readonly string $connection,
        public readonly string $queue,
        public readonly ?int $duration,
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
