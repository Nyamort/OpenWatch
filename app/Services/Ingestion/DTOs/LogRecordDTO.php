<?php

namespace App\Services\Ingestion\DTOs;

class LogRecordDTO extends RecordDTO
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
        public readonly string $level,
        public readonly string $message,
        public readonly string $context,
        public readonly string $extra,
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
