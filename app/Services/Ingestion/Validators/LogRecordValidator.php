<?php

namespace App\Services\Ingestion\Validators;

class LogRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['execution_source', 'execution_id', 'level', 'message'];
    }
}
