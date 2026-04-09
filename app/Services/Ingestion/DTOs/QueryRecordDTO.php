<?php

namespace App\Services\Ingestion\DTOs;

class QueryRecordDTO extends RecordDTO
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
        public readonly string $sql,
        public readonly ?string $file,
        public readonly ?int $line,
        public readonly string $connection,
        public readonly string $connectionType,
        public readonly int $duration,
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
