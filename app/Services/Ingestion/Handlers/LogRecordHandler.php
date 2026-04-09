<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\LogRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class LogRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_logs';
    }

    public function parse(array $raw): ?LogRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['execution_source', 'execution_id', 'level', 'message'])) {
            return null;
        }

        return new LogRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            traceId: $raw['trace_id'] ?? null,
            executionId: $raw['execution_id'] ?? null,
            executionSource: (string) ($raw['execution_source'] ?? ''),
            executionStage: (string) ($raw['execution_stage'] ?? ''),
            executionPreview: ($raw['execution_preview'] ?? '') !== '' ? (string) $raw['execution_preview'] : null,
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            level: $raw['level'],
            message: $raw['message'],
            context: is_array($raw['context'] ?? null) ? json_encode($raw['context']) : (string) ($raw['context'] ?? '{}'),
            extra: is_array($raw['extra'] ?? null) ? json_encode($raw['extra']) : (string) ($raw['extra'] ?? '{}'),
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var LogRecordDTO $dto */
        return [
            'trace_id' => $dto->traceId,
            'execution_id' => $dto->executionId,
            'execution_source' => $dto->executionSource,
            'execution_stage' => $dto->executionStage,
            'execution_preview' => $dto->executionPreview,
            'user' => $dto->user,
            'level' => $dto->level,
            'message' => $dto->message,
            'context' => $dto->context,
            'extra' => $dto->extra,
        ];
    }
}
