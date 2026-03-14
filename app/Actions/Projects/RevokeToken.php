<?php

namespace App\Actions\Projects;

use App\Models\ProjectToken;

class RevokeToken
{
    /**
     * Immediately revoke the given token.
     */
    public function handle(ProjectToken $token): void
    {
        $token->update(['status' => 'revoked']);
    }
}
