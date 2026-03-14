<?php

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\RevokeToken;
use App\Actions\Projects\RotateToken;
use App\Http\Controllers\Controller;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectTokenController extends Controller
{
    /**
     * Rotate the token for the environment (or create first one).
     */
    public function store(
        Request $request,
        Organization $organization,
        Project $project,
        Environment $environment,
        RotateToken $action
    ): RedirectResponse {
        $activeToken = $environment->projectTokens()->where('status', 'active')->first();

        if ($activeToken !== null) {
            $result = $action->handle($activeToken);
        } else {
            $result = app(\App\Actions\Projects\GenerateToken::class)->handle($environment);
        }

        return to_route('organizations.projects.environments.show', [$organization, $project, $environment])
            ->with('raw_token', $result['token']);
    }

    /**
     * Revoke the specified token.
     */
    public function destroy(
        Organization $organization,
        Project $project,
        Environment $environment,
        ProjectToken $token,
        RevokeToken $action
    ): RedirectResponse {
        $action->handle($token);

        return to_route('organizations.projects.environments.show', [$organization, $project, $environment]);
    }
}
