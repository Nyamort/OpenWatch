<?php

namespace App\Jobs;

use App\Events\TelemetryBatchIngested;
use App\Models\Environment;
use App\Services\ClickHouse\ClickHouseService;
use App\Services\Ingestion\RecordExtractorRegistry;
use App\Services\Ingestion\RecordValidatorRegistry;
use Carbon\Carbon;
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

    public function handle(RecordValidatorRegistry $validatorRegistry, RecordExtractorRegistry $extractorRegistry, ClickHouseService $clickhouse): void
    {
        $environment = Environment::find($this->environmentId);

        if ($environment === null) {
            return;
        }

        $extractionRows = [];
        $validatedRecords = [];

        foreach ($this->records as $record) {
            try {
                if (! $validatorRegistry->validate($record)) {
                    Log::info('Invalid telemetry record', ['record' => $record]);

                    continue;
                }

                $validatedRecords[] = $record;

                $type = $record['t'];
                $recordedAt = Carbon::createFromTimestamp((float) $record['timestamp'])->utc()->format('Y-m-d H:i:s.u');

                $extractor = $extractorRegistry->for($type);

                if ($extractor !== null) {
                    $extractionRows[$extractor->table()][] = $extractor->extract($record, $this->environmentId, $recordedAt);
                }
            } catch (InvalidArgumentException $e) {
                report($e);

                continue;
            }
        }

        foreach ($extractionRows as $table => $rows) {
            $clickhouse->insert($table, $rows);
        }

        if (! empty($validatedRecords)) {
            TelemetryBatchIngested::dispatch($this->environmentId, $validatedRecords);
        }
    }
}
