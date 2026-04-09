<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\CacheEventRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class CacheEventRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_cache_events';
    }

    public function parse(array $raw): ?CacheEventRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['execution_source', 'execution_id', 'store', 'key', 'type', 'duration'])) {
            return null;
        }

        return new CacheEventRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            traceId: $raw['trace_id'] ?? null,
            executionId: $raw['execution_id'] ?? null,
            executionSource: (string) ($raw['execution_source'] ?? ''),
            executionStage: (string) ($raw['execution_stage'] ?? ''),
            executionPreview: ($raw['execution_preview'] ?? '') !== '' ? (string) $raw['execution_preview'] : null,
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            store: $raw['store'],
            key: $raw['key'],
            type: $raw['type'],
            duration: (int) $raw['duration'],
            ttl: isset($raw['ttl']) ? (int) $raw['ttl'] : null,
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var CacheEventRecordDTO $dto */
        return [
            'trace_id' => $dto->traceId,
            'execution_id' => $dto->executionId,
            'execution_source' => $dto->executionSource,
            'execution_stage' => $dto->executionStage,
            'execution_preview' => $dto->executionPreview,
            'user' => $dto->user,
            'store' => $dto->store,
            'key' => $dto->key,
            'type' => $dto->type,
            'duration' => $dto->duration,
            'ttl' => $dto->ttl,
        ];
    }
}
