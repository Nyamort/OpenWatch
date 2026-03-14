<?php

namespace App\Services\Ingestion\Validators;

class CommandRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['name', 'exit_code', 'duration'];
    }
}
