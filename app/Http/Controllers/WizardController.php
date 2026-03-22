<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WizardController extends Controller
{
    /**
     * Create a new application and its first environment in one step.
     * Returns JSON so the setup wizard dialog can display the generated token.
     */
    public function store(Request $request, CreateProject $createProject, GenerateToken $generateToken): JsonResponse
    {
        $data = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'app_name' => ['required', 'string', 'max:255'],
            'app_slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'env_name' => ['required', 'string', 'max:255'],
            'env_slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'env_type' => ['required', 'string', 'in:production,staging,development,custom'],
            'env_color' => ['nullable', 'string', 'max:20'],
            'env_url' => ['nullable', 'url', 'max:500'],
        ]);

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
}
