<?php

namespace App\Services\Ingestion\Validators;

class QueuedJobRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['execution_source', 'execution_id', 'job_id', 'name', 'connection', 'queue'];
    }
}
