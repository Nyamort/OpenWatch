<?php

namespace App\Services\Ingestion\Validators;

abstract class BaseRecordValidator
{
    /**
     * @var list<string>
     */
    protected array $baseRequired = ['v', 't', 'timestamp', 'deploy', 'server'];

    /**
     * Validate the record against base and type-specific required fields.
     *
     * @param  array<string, mixed>  $record
     */
    public function validate(array $record): bool
    {
        foreach ($this->baseRequired as $field) {
            if (! array_key_exists($field, $record)) {
                return false;
            }
        }

        $hasGroup = ! empty($record['_group']);
        $hasTrace = ! empty($record['trace_id']);

        if (! $hasGroup && ! $hasTrace) {
            return false;
        }

        foreach ($this->requiredFields() as $field) {
            if (! array_key_exists($field, $record)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the type-specific required field names.
     *
     * @return list<string>
     */
    abstract protected function requiredFields(): array;
}
