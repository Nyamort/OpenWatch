<?php

namespace App\Services\Ingestion\Extractors;

class LogRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_logs';
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
            'level' => $record['level'],
            'message' => $record['message'],
            'context' => is_array($record['context'] ?? null) ? json_encode($record['context']) : (string) ($record['context'] ?? '{}'),
            'extra' => is_array($record['extra'] ?? null) ? json_encode($record['extra']) : (string) ($record['extra'] ?? '{}'),
        ];
    }
}
