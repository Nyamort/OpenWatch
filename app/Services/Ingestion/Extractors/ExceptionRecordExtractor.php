<?php

namespace App\Services\Ingestion\Extractors;

class ExceptionRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_exceptions';
    }

    protected function typeFields(array $record): array
    {
        return [
            'trace_id' => $record['trace_id'] ?? null,
            'execution_id' => $record['execution_id'] ?? null,
            'execution_source' => (string) ($record['execution_source'] ?? ''),
            'execution_stage' => (string) ($record['execution_stage'] ?? ''),
            'execution_preview' => ($record['execution_preview'] ?? '') !== '' ? (string) $record['execution_preview'] : null,
            'group_key' => $record['_group'] ?? null,
            'user' => ($record['user'] ?? '') !== '' ? (string) $record['user'] : null,
            'class' => $record['class'],
            'file' => $record['file'] ?? null,
            'line' => isset($record['line']) ? (int) $record['line'] : null,
            'message' => $record['message'],
            'code' => isset($record['code']) ? (string) $record['code'] : null,
            'trace' => is_array($record['trace'] ?? null) ? json_encode($record['trace']) : (string) ($record['trace'] ?? ''),
            'handled' => (int) ($record['handled'] ?? 0),
            'php_version' => $record['php_version'] ?? null,
            'laravel_version' => $record['laravel_version'] ?? null,
        ];
    }
}
