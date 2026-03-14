<?php

namespace App\Actions\Projects;

use App\Models\Environment;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

class CreateEnvironment
{
    public function __construct(public GenerateToken $generateToken) {}

    /**
     * Create a new environment within the given project.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(Project $project, array $data): Environment
    {
        if ($project->environments()->where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'The slug has already been taken within this project.',
            ]);
        }

        $environment = $project->environments()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'type' => $data['type'],
            'status' => $data['status'] ?? 'active',
            'health_status' => $data['health_status'] ?? 'inactive',
        ]);

        $this->generateToken->handle($environment);

        return $environment;
    }
}
