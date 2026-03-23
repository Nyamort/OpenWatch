<?php

namespace App\Actions\Projects;

use App\Models\Environment;

final class EnvironmentCreated
{
    public function __construct(
        public readonly Environment $environment,
        public readonly string $token,
    ) {}
}
