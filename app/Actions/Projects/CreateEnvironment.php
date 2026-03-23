<?php

namespace App\Actions\Projects;

use App\Models\Environment;
use App\Models\Project;

class CreateEnvironment
{
    public function __construct(public GenerateToken $generateToken) {}

    /**
     * Create a new environment within the given project.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, array $data): Environment
    {
        $environment = $project->environments()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'color' => $data['color'] ?? null,
            'status' => $data['status'] ?? 'active',
            'health_status' => $data['health_status'] ?? 'inactive',
        ]);

        $this->generateToken->handle($environment);

        return $environment;
    }
}
