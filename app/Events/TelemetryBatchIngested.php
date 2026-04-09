<?php

namespace App\Events;

use App\Services\Ingestion\DTOs\RecordDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelemetryBatchIngested
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, RecordDTO>  $records
     */
    public function __construct(
        public readonly int $environmentId,
        public readonly array $records,
    ) {}
}
