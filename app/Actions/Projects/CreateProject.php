<?php

namespace App\Actions\Projects;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

class CreateProject
{
    /**
     * Create a new project within the given organization.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(Organization $org, array $data): Project
    {
        if ($org->projects()->where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'The slug has already been taken within this organization.',
            ]);
        }

        return $org->projects()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);
    }
}
