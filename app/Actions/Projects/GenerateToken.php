<?php

namespace App\Actions\Projects;

use App\Models\Environment;
use App\Models\ProjectToken;

class GenerateToken
{
    /**
     * Generate a new ingest token for the given environment.
     *
     * Returns the raw token (shown once) and the persisted model.
     *
     * @return array{token: string, model: ProjectToken}
     */
    public function handle(Environment $environment): array
    {
        $rawToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $tokenHash = hash('sha256', $rawToken);

        $projectToken = $environment->projectTokens()->create([
            'token_hash' => $tokenHash,
            'status' => 'active',
        ]);

        return [
            'token' => $rawToken,
            'model' => $projectToken,
        ];
    }
}
