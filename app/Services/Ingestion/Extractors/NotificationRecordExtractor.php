<?php

namespace App\Services\Ingestion\Extractors;

class NotificationRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_notifications';
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
            'channel' => $record['channel'],
            'class' => $record['class'],
            'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
            'failed' => (int) ($record['failed'] ?? 0),
        ];
    }
}
