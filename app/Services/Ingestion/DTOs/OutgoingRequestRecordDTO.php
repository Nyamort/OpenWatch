<?php

namespace App\Services\Ingestion\DTOs;

class OutgoingRequestRecordDTO extends RecordDTO
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
        public readonly string $host,
        public readonly string $method,
        public readonly string $url,
        public readonly ?int $statusCode,
        public readonly int $duration,
        public readonly ?int $requestSize,
        public readonly ?int $responseSize,
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
