<?php

namespace App\Services\Ingestion\Validators;

class OutgoingRequestRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['execution_source', 'execution_id', 'host', 'method', 'url', 'duration'];
    }
}
