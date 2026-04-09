<?php

namespace App\Services\Ingestion\Extractors;

use Illuminate\Support\Str;

abstract class BaseRecordExtractor
{
    /**
     * Return the ClickHouse table name for this record type.
     */
    abstract public function table(): string;

    /**
     * Return the type-specific fields for this record.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    abstract protected function typeFields(array $record): array;

    /**
     * Build the full extraction row by merging base fields with type-specific fields.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public function extract(array $record, int $environmentId, string $recordedAt): array
    {
        return array_merge([
            'id' => Str::uuid()->toString(),
            'environment_id' => $environmentId,
            'deploy' => (string) ($record['deploy'] ?? ''),
            'server' => (string) ($record['server'] ?? ''),
            'recorded_at' => $recordedAt,
        ], $this->typeFields($record));
    }
}
