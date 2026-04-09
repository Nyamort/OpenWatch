<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\NotificationRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class NotificationRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_notifications';
    }

    public function parse(array $raw): ?NotificationRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['execution_source', 'execution_id', 'channel', 'class', 'duration'])) {
            return null;
        }

        return new NotificationRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            traceId: $raw['trace_id'] ?? null,
            executionId: $raw['execution_id'] ?? null,
            executionSource: (string) ($raw['execution_source'] ?? ''),
            executionStage: (string) ($raw['execution_stage'] ?? ''),
            executionPreview: ($raw['execution_preview'] ?? '') !== '' ? (string) $raw['execution_preview'] : null,
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            channel: $raw['channel'],
            class: $raw['class'],
            duration: isset($raw['duration']) ? (int) $raw['duration'] : null,
            failed: (int) ($raw['failed'] ?? 0),
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var NotificationRecordDTO $dto */
        return [
            'trace_id' => $dto->traceId,
            'execution_id' => $dto->executionId,
            'execution_source' => $dto->executionSource,
            'execution_stage' => $dto->executionStage,
            'execution_preview' => $dto->executionPreview,
            'user' => $dto->user,
            'channel' => $dto->channel,
            'class' => $dto->class,
            'duration' => $dto->duration,
            'failed' => $dto->failed,
        ];
    }
}
