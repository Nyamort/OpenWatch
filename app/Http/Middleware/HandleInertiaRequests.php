<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $activeOrg = null;
        $activeProject = null;
        $activeEnvironment = null;
        $organizations = [];
        $projects = [];
        $environments = [];

        if ($user) {
            $organizations = $user->organizations()
                ->select(['organizations.id', 'organizations.name', 'organizations.slug'])
                ->orderBy('organizations.name')
                ->get()
                ->toArray();

            if ($user->active_organization_id) {
                $activeOrg = collect($organizations)->firstWhere('id', $user->active_organization_id)
                    ?? $user->activeOrganization()->select(['id', 'name', 'slug'])->first()?->toArray();

                $projects = \App\Models\Project::query()
                    ->where('organization_id', $user->active_organization_id)
                    ->select(['id', 'name', 'slug'])
                    ->orderBy('name')
                    ->get()
                    ->toArray();
            }

            if ($user->active_project_id) {
                $activeProject = collect($projects)->firstWhere('id', $user->active_project_id)
                    ?? $user->activeProject()->select(['id', 'name', 'slug'])->first()?->toArray();

                $environments = \App\Models\Environment::query()
                    ->where('project_id', $user->active_project_id)
                    ->select(['id', 'name', 'slug'])
                    ->orderBy('name')
                    ->get()
                    ->toArray();
            }

            if ($user->active_environment_id) {
                $activeEnvironment = collect($environments)->firstWhere('id', $user->active_environment_id)
                    ?? $user->activeEnvironment()->select(['id', 'name', 'slug'])->first()?->toArray();
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'timezone' => $user?->timezone,
                'locale' => $user?->locale,
            ],
            'activeOrganization' => $activeOrg,
            'activeProject' => $activeProject,
            'activeEnvironment' => $activeEnvironment,
            'organizations' => $organizations,
            'projects' => $projects,
            'environments' => $environments,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
