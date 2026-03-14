<?php

namespace App\Services\Ingestion\Validators;

class ExceptionRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['execution_source', 'execution_id', 'class', 'message', 'trace'];
    }
}
