<?php

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateProject;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the organization's projects.
     */
    public function index(Organization $organization): Response
    {
        $projects = $organization->projects()->active()->get();

        return Inertia::render('projects/index', [
            'organization' => $organization,
            'projects' => $projects,
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request, Organization $organization, CreateProject $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'description' => ['nullable', 'string'],
        ]);

        $project = $action->handle($organization, $data);

        return to_route('organizations.projects.show', [$organization, $project]);
    }

    /**
     * Display the specified project.
     */
    public function show(Organization $organization, Project $project): Response
    {
        return Inertia::render('projects/show', [
            'organization' => $organization,
            'project' => $project,
        ]);
    }

    /**
     * Archive (soft-delete) the specified project.
     */
    public function destroy(Organization $organization, Project $project, ArchiveProject $action): RedirectResponse
    {
        $action->handle($project);

        return to_route('organizations.projects.index', $organization);
    }
}
