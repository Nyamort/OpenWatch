<?php

namespace App\Services\Ingestion\Validators;

class QueryRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['execution_source', 'execution_id', 'sql', 'duration', 'connection', 'connection_type'];
    }
}
