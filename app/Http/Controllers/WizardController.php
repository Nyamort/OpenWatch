<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
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
    public function store(StoreWizardAppRequest $request, CreateProject $createProject, CreateEnvironment $createEnvironment): JsonResponse
    {
        $data = $request->validated();

        $org = Organization::findOrFail($data['organization_id']);

        $project = $createProject->handle($org, [
            'name' => $data['app_name'],
            'description' => null,
        ]);

        $result = $createEnvironment->handle($project, [
            'name' => $data['env_name'],
            'type' => $data['env_type'],
            'color' => $data['env_color'] ?? null,
        ]);

        $user = $request->user();
        $user->active_organization_id = $org->id;
        $user->active_project_id = $project->id;
        $user->active_environment_id = $result->environment->id;
        $user->save();

        return response()->json([
            'project' => ['id' => $project->id, 'name' => $project->name, 'slug' => $project->slug],
            'environment' => ['id' => $result->environment->id, 'name' => $result->environment->name, 'slug' => $result->environment->slug],
            'token' => $result->token,
        ]);
    }

    /**
     * Update the application name and its environment details.
     */
    public function update(UpdateWizardAppRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

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
