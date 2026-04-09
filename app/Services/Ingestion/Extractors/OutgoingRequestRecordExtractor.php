<?php

namespace App\Services\Ingestion\Extractors;

class OutgoingRequestRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_outgoing_requests';
    }

    protected function typeFields(array $record): array
    {
        return [
            'trace_id' => $record['trace_id'] ?? null,
            'execution_id' => $record['execution_id'] ?? null,
            'execution_source' => (string) ($record['execution_source'] ?? ''),
            'execution_stage' => (string) ($record['execution_stage'] ?? ''),
            'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
            'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
            'host' => $record['host'],
            'method' => $record['method'],
            'url' => $record['url'],
            'status_code' => isset($record['status_code']) ? (int) $record['status_code'] : null,
            'duration' => (int) $record['duration'],
            'request_size' => isset($record['request_size']) ? (int) $record['request_size'] : null,
            'response_size' => isset($record['response_size']) ? (int) $record['response_size'] : null,
        ];
    }
}
