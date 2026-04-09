<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\QueuedJobRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class QueuedJobRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_queued_jobs';
    }

    public function parse(array $raw): ?QueuedJobRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['execution_source', 'execution_id', 'job_id', 'name', 'connection', 'queue'])) {
            return null;
        }

        return new QueuedJobRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            traceId: $raw['trace_id'] ?? null,
            executionId: $raw['execution_id'] ?? null,
            executionSource: (string) ($raw['execution_source'] ?? ''),
            executionStage: (string) ($raw['execution_stage'] ?? ''),
            executionPreview: ($raw['execution_preview'] ?? '') !== '' ? (string) $raw['execution_preview'] : null,
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            jobId: $raw['job_id'],
            name: $raw['name'],
            connection: $raw['connection'],
            queue: $raw['queue'],
            duration: isset($raw['duration']) ? (int) $raw['duration'] : null,
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var QueuedJobRecordDTO $dto */
        return [
            'trace_id' => $dto->traceId,
            'execution_id' => $dto->executionId,
            'execution_source' => $dto->executionSource,
            'execution_stage' => $dto->executionStage,
            'execution_preview' => $dto->executionPreview,
            'user' => $dto->user,
            'job_id' => $dto->jobId,
            'name' => $dto->name,
            'connection' => $dto->connection,
            'queue' => $dto->queue,
            'duration' => $dto->duration,
        ];
    }
}
