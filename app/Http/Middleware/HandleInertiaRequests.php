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
        $organizations = collect();
        $projectGroups = [];

        if ($user) {
            $organizations = $user->organizations()
                ->select(['organizations.id', 'organizations.name', 'organizations.slug'])
                ->orderBy('organizations.name')
                ->get();

            foreach ($organizations as $org) {
                $projects = \App\Models\Project::query()
                    ->where('organization_id', $org->id)
                    ->with([
                        'environments' => fn ($q) => $q->select(['id', 'project_id', 'name', 'slug'])->orderBy('name'),
                        'media',
                    ])
                    ->select(['id', 'organization_id', 'name', 'slug'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'slug' => $p->slug,
                        'logo_url' => $p->getFirstMediaUrl('logo'),
                        'environments' => $p->environments->map(fn ($e) => ['id' => $e->id, 'name' => $e->name, 'slug' => $e->slug])->values()->toArray(),
                    ])
                    ->values()
                    ->toArray();

                $projectGroups[] = [
                    'organization' => ['id' => $org->id, 'name' => $org->name, 'slug' => $org->slug],
                    'projects' => $projects,
                ];

                if ($org->id === $user->active_organization_id) {
                    $activeOrg = ['id' => $org->id, 'name' => $org->name, 'slug' => $org->slug];

                    $activeProjectData = collect($projects)->firstWhere('id', $user->active_project_id);
                    if ($activeProjectData) {
                        $activeProject = $activeProjectData;
                        $activeEnvironment = collect($activeProjectData['environments'])->firstWhere('id', $user->active_environment_id);
                    }
                }
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
            'organizations' => $organizations->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'slug' => $o->slug])->values()->toArray(),
            'projectGroups' => $projectGroups,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
