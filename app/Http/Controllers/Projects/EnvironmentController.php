<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\Project;
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
