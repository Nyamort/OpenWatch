<?php

namespace App\Services\Ingestion;

use App\Models\ProjectToken;

class ValidateIngestToken
{
    /**
     * Validate the raw token and return a result containing the environment (if valid)
     * and whether the token was found but revoked.
     */
    public function validate(string $rawToken): TokenValidationResult
    {
        $tokenHash = hash('sha256', $rawToken);

        $projectToken = ProjectToken::query()
            ->with('environment')
            ->where('token_hash', $tokenHash)
            ->whereIn('status', ['active', 'deprecated', 'revoked'])
            ->first();

        if ($projectToken === null) {
            return new TokenValidationResult(environment: null, isRevoked: false);
        }

        if ($projectToken->status === 'revoked') {
            return new TokenValidationResult(environment: null, isRevoked: true);
        }

        $isValid = $projectToken->status === 'active'
            || ($projectToken->status === 'deprecated' && $projectToken->grace_until?->isFuture());

        if (! $isValid) {
            return new TokenValidationResult(environment: null, isRevoked: false);
        }

        return new TokenValidationResult(environment: $projectToken->environment, isRevoked: false);
    }
}
