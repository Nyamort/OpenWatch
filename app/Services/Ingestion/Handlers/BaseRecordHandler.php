<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\RecordDTO;
use Carbon\Carbon;
use Illuminate\Support\Str;

abstract class BaseRecordHandler
{
    abstract public function table(): string;

    /**
     * Parse and validate a raw record into a typed DTO, or return null if invalid.
     *
     * @param  array<string, mixed>  $raw
     */
    abstract public function parse(array $raw): ?RecordDTO;

    /**
     * Return the type-specific ClickHouse fields for the given DTO.
     *
     * @return array<string, mixed>
     */
    abstract protected function typeFields(RecordDTO $dto): array;

    /**
     * Build the full ClickHouse extraction row.
     *
     * @return array<string, mixed>
     */
    public function extract(RecordDTO $dto, int $environmentId): array
    {
        $recordedAt = Carbon::createFromTimestamp($dto->timestamp)->utc()->format('Y-m-d H:i:s.u');

        return array_merge([
            'id' => Str::uuid()->toString(),
            'environment_id' => $environmentId,
            'deploy' => $dto->deploy,
            'server' => $dto->server,
            'recorded_at' => $recordedAt,
        ], $this->typeFields($dto));
    }

    /**
     * Check that all base fields (v, t, timestamp, deploy, server) are present.
     *
     * @param  array<string, mixed>  $raw
     */
    protected function hasBaseFields(array $raw): bool
    {
        foreach (['v', 't', 'timestamp', 'deploy', 'server'] as $field) {
            if (! array_key_exists($field, $raw)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check that the record carries either a group key or a trace ID.
     *
     * @param  array<string, mixed>  $raw
     */
    protected function hasExecutionContext(array $raw): bool
    {
        return ! empty($raw['_group']) || ! empty($raw['trace_id']);
    }

    /**
     * Check that all given field keys exist in the raw record.
     *
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $fields
     */
    protected function hasFields(array $raw, array $fields): bool
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $raw)) {
                return false;
            }
        }

        return true;
    }
}
