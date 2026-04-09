<?php

namespace App\Services\Ingestion\DTOs;

class ExceptionRecordDTO extends RecordDTO
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
        public readonly ?string $groupKey,
        public readonly ?string $user,
        public readonly string $class,
        public readonly ?string $file,
        public readonly ?int $line,
        public readonly string $message,
        public readonly ?string $code,
        public readonly string $trace,
        public readonly int $handled,
        public readonly ?string $phpVersion,
        public readonly ?string $laravelVersion,
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
