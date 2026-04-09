<?php

namespace App\Services\Ingestion\Extractors;

class QueryRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_queries';
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
            'sql_hash' => hash('sha256', $record['sql']),
            'sql_normalized' => $record['sql'],
            'file' => ($record['file'] ?? '') !== '' ? (string) $record['file'] : null,
            'line' => isset($record['line']) && $record['line'] > 0 ? (int) $record['line'] : null,
            'connection' => $record['connection'],
            'connection_type' => $record['connection_type'],
            'duration' => (int) $record['duration'],
        ];
    }
}
