<?php

namespace App\Services\Ingestion\Validators;

class CacheEventRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['execution_source', 'execution_id', 'store', 'key', 'type', 'duration'];
    }
}
