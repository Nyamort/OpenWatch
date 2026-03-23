<?php

namespace App\Actions\Projects;

use App\Models\Organization;
use App\Models\Project;

class CreateProject
{
    /**
     * Create a new project within the given organization.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Organization $org, array $data): Project
    {
        return $org->projects()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }
}
