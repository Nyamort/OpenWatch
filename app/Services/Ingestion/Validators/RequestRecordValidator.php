<?php

namespace App\Services\Ingestion\Validators;

class RequestRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['user', 'method', 'url', 'route_name', 'status_code', 'duration', 'ip'];
    }
}
