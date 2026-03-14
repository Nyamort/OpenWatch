<?php

namespace App\Services\Ingestion\Validators;

class JobAttemptRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['execution_id', 'job_id', 'attempt_id', 'attempt', 'name', 'status'];
    }
}
