<?php

namespace App\Services\Ingestion\DTOs;

class NotificationRecordDTO extends RecordDTO
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
        public readonly string $channel,
        public readonly string $class,
        public readonly ?int $duration,
        public readonly int $failed,
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
