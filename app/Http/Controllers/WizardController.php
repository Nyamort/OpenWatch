<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Http\Requests\Wizard\StoreWizardAppRequest;
use App\Http\Requests\Wizard\UpdateWizardAppRequest;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class WizardController extends Controller
{
    /**
     * Create a new application and its first environment in one step.
     * Returns JSON so the setup wizard dialog can display the generated token.
     */
    public function store(StoreWizardAppRequest $request, CreateProject $createProject, GenerateToken $generateToken): JsonResponse
    {
        $data = $request->validated();

        $org = Organization::findOrFail($data['organization_id']);

        $project = $createProject->handle($org, [
            'name' => $data['app_name'],
            'slug' => $data['app_slug'],
            'description' => null,
        ]);

        $environment = $project->environments()->create([
            'name' => $data['env_name'],
            'slug' => $data['env_slug'],
            'type' => $data['env_type'],
            'color' => $data['env_color'] ?? null,
            'status' => 'active',
            'health_status' => 'inactive',
        ]);

        ['token' => $rawToken] = $generateToken->handle($environment);

        $user = $request->user();
        $user->active_organization_id = $org->id;
        $user->active_project_id = $project->id;
        $user->active_environment_id = $environment->id;
        $user->save();

        return response()->json([
            'project' => ['id' => $project->id, 'name' => $project->name, 'slug' => $project->slug],
            'environment' => ['id' => $environment->id, 'name' => $environment->name, 'slug' => $environment->slug],
            'token' => $rawToken,
        ]);
    }

    public function update(UpdateWizardAppRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();

        $project->update(['name' => $data['app_name']]);

        $environment = $project->environments()->findOrFail($data['env_id']);
        $environment->update([
            'name' => $data['env_name'],
            'type' => $data['env_type'],
            'color' => $data['env_color'] ?? null,
        ]);

        return response()->json([
            'project' => ['id' => $project->id, 'name' => $project->name, 'slug' => $project->slug],
            'environment' => ['id' => $environment->id, 'name' => $environment->name, 'slug' => $environment->slug],
        ]);
    }
}
