<?php

namespace App\Services\Ingestion;

use App\Models\Environment;

readonly class TokenValidationResult
{
    public function __construct(
        public ?Environment $environment,
        public bool $isRevoked,
    ) {}
}
