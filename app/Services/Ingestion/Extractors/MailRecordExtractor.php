<?php

namespace App\Services\Ingestion\Extractors;

class MailRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_mails';
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
            'mailer' => $record['mailer'],
            'class' => $record['class'],
            'subject' => $record['subject'],
            'to' => (int) ($record['to'] ?? 0),
            'cc' => (int) ($record['cc'] ?? 0),
            'bcc' => (int) ($record['bcc'] ?? 0),
            'attachments' => (int) ($record['attachments'] ?? 0),
            'duration' => isset($record['duration']) ? (int) $record['duration'] : null,
            'failed' => (int) ($record['failed'] ?? 0),
        ];
    }
}
