<?php

namespace App\Jobs;

use App\Events\TelemetryBatchIngested;
use App\Models\Environment;
use App\Services\ClickHouse\ClickHouseService;
use App\Services\Ingestion\RecordHandlerRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProcessTelemetryBatch implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    public function __construct(
        public readonly int $environmentId,
        public readonly array $records,
        public readonly string $requestId,
    ) {}

    public function handle(RecordHandlerRegistry $registry, ClickHouseService $clickhouse): void
    {
        $environment = Environment::find($this->environmentId);

        if ($environment === null) {
            return;
        }

        $extractionRows = [];
        $parsedDtos = [];

        foreach ($this->records as $record) {
            try {
                $type = $record['t'] ?? null;
                $handler = $registry->for($type);
                $dto = $handler->parse($record);

                if ($dto === null) {
                    Log::info('Invalid telemetry record', ['record' => $record]);

                    continue;
                }

                $parsedDtos[] = $dto;
                $extractionRows[$handler->table()][] = $handler->extract($dto, $this->environmentId);
            } catch (InvalidArgumentException $e) {
                report($e);

                continue;
            }
        }

        foreach ($extractionRows as $table => $rows) {
            $clickhouse->insert($table, $rows);
        }

        if (! empty($parsedDtos)) {
            TelemetryBatchIngested::dispatch($this->environmentId, $parsedDtos);
        }
    }
}
