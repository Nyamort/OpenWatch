<?php

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\CreateEnvironment;
use App\Http\Controllers\Controller;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnvironmentController extends Controller
{
    /**
     * Display a listing of the project's environments.
     */
    public function index(Organization $organization, Project $project): Response
    {
        $environments = $project->environments()->active()->get();

        return Inertia::render('environments/index', [
            'organization' => $organization,
            'project' => $project,
            'environments' => $environments,
        ]);
    }

    /**
     * Store a newly created environment.
     */
    public function store(Request $request, Organization $organization, Project $project, CreateEnvironment $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'type' => ['required', 'string', 'in:production,staging,development,custom'],
        ]);

        $environment = $action->handle($project, $data);

        return to_route('organizations.projects.environments.show', [$organization, $project, $environment]);
    }

    /**
     * Display the specified environment.
     */
    public function show(Organization $organization, Project $project, Environment $environment): Response
    {
        return Inertia::render('environments/show', [
            'organization' => $organization,
            'project' => $project,
            'environment' => $environment,
        ]);
    }
}
