<?php

namespace App\Services\Analytics;

use App\Models\Environment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AnalyticsContextResolver
{
    /**
     * Resolve the analytics context from slugs.
     *
     * @throws AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolve(
        string $orgSlug,
        string $projectSlug,
        string $envSlug,
        User $user,
    ): AnalyticsContext {
        $organization = Organization::query()->where('slug', $orgSlug)->firstOrFail();
        $project = Project::query()->where('slug', $projectSlug)->firstOrFail();

        if ($project->organization_id !== $organization->id) {
            throw new AuthorizationException('Project does not belong to this organization.');
        }

        $environment = Environment::query()
            ->where('slug', $envSlug)
            ->where('project_id', $project->id)
            ->firstOrFail();

        return new AnalyticsContext(
            organization: $organization,
            project: $project,
            environment: $environment,
        );
    }
}
