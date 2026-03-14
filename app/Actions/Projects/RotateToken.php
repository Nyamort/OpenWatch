<?php

namespace App\Actions\Projects;

use App\Models\ProjectToken;

class RotateToken
{
    public function __construct(public GenerateToken $generateToken) {}

    /**
     * Rotate a token, placing the old one in a grace period.
     *
     * @return array{token: string, model: ProjectToken}
     */
    public function handle(ProjectToken $token, int $graceDays = 3): array
    {
        $token->update([
            'status' => 'deprecated',
            'grace_until' => now()->addDays($graceDays),
            'rotated_at' => now(),
        ]);

        return $this->generateToken->handle($token->environment);
    }
}
