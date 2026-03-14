<?php

namespace App\Services\Ingestion\Validators;

class NotificationRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['execution_source', 'execution_id', 'channel', 'class', 'duration'];
    }
}
