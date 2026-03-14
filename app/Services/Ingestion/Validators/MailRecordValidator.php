<?php

namespace App\Services\Ingestion\Validators;

class MailRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['execution_source', 'execution_id', 'mailer', 'class', 'subject', 'to'];
    }
}
