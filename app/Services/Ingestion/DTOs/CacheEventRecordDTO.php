<?php

namespace App\Services\Ingestion\DTOs;

class CacheEventRecordDTO extends RecordDTO
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
        public readonly string $store,
        public readonly string $key,
        public readonly string $type,
        public readonly int $duration,
        public readonly ?int $ttl,
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
