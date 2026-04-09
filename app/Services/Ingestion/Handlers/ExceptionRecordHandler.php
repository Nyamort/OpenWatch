<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\ExceptionRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class ExceptionRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_exceptions';
    }

    public function parse(array $raw): ?ExceptionRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['execution_source', 'execution_id', 'class', 'message', 'trace'])) {
            return null;
        }

        return new ExceptionRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            traceId: $raw['trace_id'] ?? null,
            executionId: $raw['execution_id'] ?? null,
            executionSource: (string) ($raw['execution_source'] ?? ''),
            executionStage: (string) ($raw['execution_stage'] ?? ''),
            executionPreview: ($raw['execution_preview'] ?? '') !== '' ? (string) $raw['execution_preview'] : null,
            groupKey: $raw['_group'] ?? null,
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            class: $raw['class'],
            file: $raw['file'] ?? null,
            line: isset($raw['line']) ? (int) $raw['line'] : null,
            message: $raw['message'],
            code: isset($raw['code']) ? (string) $raw['code'] : null,
            trace: is_array($raw['trace'] ?? null) ? json_encode($raw['trace']) : (string) ($raw['trace'] ?? ''),
            handled: (int) ($raw['handled'] ?? 0),
            phpVersion: $raw['php_version'] ?? null,
            laravelVersion: $raw['laravel_version'] ?? null,
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var ExceptionRecordDTO $dto */
        return [
            'trace_id' => $dto->traceId,
            'execution_id' => $dto->executionId,
            'execution_source' => $dto->executionSource,
            'execution_stage' => $dto->executionStage,
            'execution_preview' => $dto->executionPreview,
            'group_key' => $dto->groupKey,
            'user' => $dto->user,
            'class' => $dto->class,
            'file' => $dto->file,
            'line' => $dto->line,
            'message' => $dto->message,
            'code' => $dto->code,
            'trace' => $dto->trace,
            'handled' => $dto->handled,
            'php_version' => $dto->phpVersion,
            'laravel_version' => $dto->laravelVersion,
        ];
    }
}
