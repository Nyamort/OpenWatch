<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\MailRecordDTO;
use App\Services\Ingestion\DTOs\RecordDTO;

class MailRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_mails';
    }

    public function parse(array $raw): ?MailRecordDTO
    {
        if (! $this->hasBaseFields($raw)) {
            return null;
        }

        if (! $this->hasExecutionContext($raw)) {
            return null;
        }

        if (! $this->hasFields($raw, ['execution_source', 'execution_id', 'mailer', 'class', 'subject', 'to'])) {
            return null;
        }

        return new MailRecordDTO(
            timestamp: (float) $raw['timestamp'],
            deploy: (string) ($raw['deploy'] ?? ''),
            server: (string) ($raw['server'] ?? ''),
            traceId: $raw['trace_id'] ?? null,
            executionId: $raw['execution_id'] ?? null,
            executionSource: (string) ($raw['execution_source'] ?? ''),
            executionStage: (string) ($raw['execution_stage'] ?? ''),
            executionPreview: ($raw['execution_preview'] ?? '') !== '' ? (string) $raw['execution_preview'] : null,
            user: ($raw['user'] ?? '') !== '' ? (string) $raw['user'] : null,
            mailer: $raw['mailer'],
            class: $raw['class'],
            subject: $raw['subject'],
            to: (int) ($raw['to'] ?? 0),
            cc: (int) ($raw['cc'] ?? 0),
            bcc: (int) ($raw['bcc'] ?? 0),
            attachments: (int) ($raw['attachments'] ?? 0),
            duration: isset($raw['duration']) ? (int) $raw['duration'] : null,
            failed: (int) ($raw['failed'] ?? 0),
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var MailRecordDTO $dto */
        return [
            'trace_id' => $dto->traceId,
            'execution_id' => $dto->executionId,
            'execution_source' => $dto->executionSource,
            'execution_stage' => $dto->executionStage,
            'execution_preview' => $dto->executionPreview,
            'user' => $dto->user,
            'mailer' => $dto->mailer,
            'class' => $dto->class,
            'subject' => $dto->subject,
            'to' => $dto->to,
            'cc' => $dto->cc,
            'bcc' => $dto->bcc,
            'attachments' => $dto->attachments,
            'duration' => $dto->duration,
            'failed' => $dto->failed,
        ];
    }
}
