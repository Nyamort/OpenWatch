<?php

namespace App\Services\Ingestion\Validators;

class UserRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['id', 'name', 'username'];
    }
}
