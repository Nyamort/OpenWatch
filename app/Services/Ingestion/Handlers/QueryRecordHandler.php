<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\QueryRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class QueryRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_queries';
    }

    public function parse(array $raw): ?QueryRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['execution_source', 'execution_id', 'sql', 'duration', 'connection', 'connection_type'])) {
            return null;
        }

        return new QueryRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            traceId: $raw['trace_id'] ?? null,
            executionId: $raw['execution_id'] ?? null,
            executionSource: (string) ($raw['execution_source'] ?? ''),
            executionStage: (string) ($raw['execution_stage'] ?? ''),
            executionPreview: ($raw['execution_preview'] ?? '') !== '' ? (string) $raw['execution_preview'] : null,
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            sql: $raw['sql'],
            file: ($raw['file'] ?? '') !== '' ? (string) $raw['file'] : null,
            line: isset($raw['line']) && $raw['line'] > 0 ? (int) $raw['line'] : null,
            connection: $raw['connection'],
            connectionType: $raw['connection_type'],
            duration: (int) $raw['duration'],
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var QueryRecordDTO $dto */
        return [
            'trace_id' => $dto->traceId,
            'execution_id' => $dto->executionId,
            'execution_source' => $dto->executionSource,
            'execution_stage' => $dto->executionStage,
            'execution_preview' => $dto->executionPreview,
            'user' => $dto->user,
            'sql_hash' => hash('sha256', $dto->sql),
            'sql_normalized' => $dto->sql,
            'file' => $dto->file,
            'line' => $dto->line,
            'connection' => $dto->connection,
            'connection_type' => $dto->connectionType,
            'duration' => $dto->duration,
        ];
    }
}
