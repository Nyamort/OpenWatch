<?php

namespace App\Services\Analytics;

use App\Models\Environment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AnalyticsContextResolver
{
    /**
     * Resolve the analytics context from an environment slug.
     *
     * @throws AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolve(string $envSlug, User $user): AnalyticsContext
    {
        $environment = Environment::query()
            ->with('project.organization')
            ->where('slug', $envSlug)
            ->firstOrFail();

        $project = $environment->project;
        $organization = $project->organization;

        return new AnalyticsContext(
            organization: $organization,
            project: $project,
            environment: $environment,
        );
    }
}
