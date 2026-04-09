<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelemetryBatchIngested
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    public function __construct(
        public readonly int $environmentId,
        public readonly array $records,
    ) {}
}
