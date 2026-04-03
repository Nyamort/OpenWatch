<?php

namespace App\Services\Ingestion\Validators;

class UserRecordValidator extends BaseRecordValidator
{
    /**
     * User records are standalone identity records and do not carry
     * execution context (deploy, server, trace_id, _group).
     *
     * {@inheritdoc}
     */
    public function validate(array $record): bool
    {
        foreach (['v', 't', 'timestamp'] as $field) {
            if (! array_key_exists($field, $record)) {
                return false;
            }
        }

        foreach ($this->requiredFields() as $field) {
            if (! array_key_exists($field, $record)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function requiredFields(): array
    {
        return ['id', 'name', 'username'];
    }
}
