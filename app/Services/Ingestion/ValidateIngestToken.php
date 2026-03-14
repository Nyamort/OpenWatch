<?php

namespace App\Services\Ingestion;

use App\Models\Environment;
use App\Models\ProjectToken;

class ValidateIngestToken
{
    /**
     * Validate the raw token and return the associated environment, or null if invalid.
     */
    public function validate(string $rawToken): ?Environment
    {
        $tokenHash = hash('sha256', $rawToken);

        $projectToken = ProjectToken::query()
            ->where('token_hash', $tokenHash)
            ->valid()
            ->first();

        if ($projectToken === null) {
            return null;
        }

        return $projectToken->environment;
    }
}
