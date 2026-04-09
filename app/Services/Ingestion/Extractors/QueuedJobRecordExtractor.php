<?php

namespace App\Services\Ingestion\Extractors;

class QueuedJobRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_queued_jobs';
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
            'job_id' => $record['job_id'],
            'name' => $record['name'],
            'connection' => $record['connection'],
            'queue' => $record['queue'],
            'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
        ];
    }
}
