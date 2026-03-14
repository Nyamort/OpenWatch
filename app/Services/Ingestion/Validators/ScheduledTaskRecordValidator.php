<?php

namespace App\Services\Ingestion\Validators;

class ScheduledTaskRecordValidator extends BaseRecordValidator
{
    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['name', 'cron', 'status', 'duration'];
    }
}
