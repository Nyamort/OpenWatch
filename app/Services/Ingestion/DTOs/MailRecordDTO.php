<?php

namespace App\Services\Ingestion\DTOs;

class MailRecordDTO extends RecordDTO
{
    public function __construct(
        float $timestamp,
        string $deploy,
        string $server,
        public readonly ?string $traceId,
        public readonly ?string $executionId,
        public readonly string $executionSource,
        public readonly string $executionStage,
        public readonly ?string $executionPreview,
        public readonly ?string $user,
        public readonly string $mailer,
        public readonly string $class,
        public readonly string $subject,
        public readonly int $to,
        public readonly int $cc,
        public readonly int $bcc,
        public readonly int $attachments,
        public readonly ?int $duration,
        public readonly int $failed,
    ) {
        parent::__construct($timestamp, $deploy, $server);
    }
}
