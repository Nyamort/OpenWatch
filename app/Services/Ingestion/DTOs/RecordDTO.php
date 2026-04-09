<?php

namespace App\Services\Ingestion\DTOs;

abstract class RecordDTO
{
    public function __construct(
        public readonly float $timestamp,
        public readonly string $deploy,
        public readonly string $server,
    ) {}
}
