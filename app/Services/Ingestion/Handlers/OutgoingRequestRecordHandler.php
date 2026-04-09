<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\OutgoingRequestRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class OutgoingRequestRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_outgoing_requests';
    }

    public function parse(array $raw): ?OutgoingRequestRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['execution_source', 'execution_id', 'host', 'method', 'url', 'duration'])) {
            return null;
        }

        return new OutgoingRequestRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            traceId: $raw['trace_id'] ?? null,
            executionId: $raw['execution_id'] ?? null,
            executionSource: (string) ($raw['execution_source'] ?? ''),
            executionStage: (string) ($raw['execution_stage'] ?? ''),
            executionPreview: ($raw['execution_preview'] ?? '') !== '' ? (string) $raw['execution_preview'] : null,
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            host: $raw['host'],
            method: $raw['method'],
            url: $raw['url'],
            statusCode: isset($raw['status_code']) ? (int) $raw['status_code'] : null,
            duration: (int) $raw['duration'],
            requestSize: isset($raw['request_size']) ? (int) $raw['request_size'] : null,
            responseSize: isset($raw['response_size']) ? (int) $raw['response_size'] : null,
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var OutgoingRequestRecordDTO $dto */
        return [
            'trace_id' => $dto->traceId,
            'execution_id' => $dto->executionId,
            'execution_source' => $dto->executionSource,
            'execution_stage' => $dto->executionStage,
            'execution_preview' => $dto->executionPreview,
            'user' => $dto->user,
            'host' => $dto->host,
            'method' => $dto->method,
            'url' => $dto->url,
            'status_code' => $dto->statusCode,
            'duration' => $dto->duration,
            'request_size' => $dto->requestSize,
            'response_size' => $dto->responseSize,
        ];
    }
}
