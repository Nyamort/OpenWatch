<?php

namespace App\Services\Analytics;

use Carbon\CarbonInterface;

class PeriodResult
{
    public function __construct(
        public readonly CarbonInterface $start,
        public readonly CarbonInterface $end,
        public readonly int $bucketSeconds,
        public readonly string $label,
    ) {}
}
