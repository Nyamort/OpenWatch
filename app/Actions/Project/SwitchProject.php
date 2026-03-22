<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SwitchProject
{
    /**
     * Switch the user's active project and reset the active environment.
     *
     * @throws ValidationException
     */
    public function handle(User $user, Project $project): void
    {
        if ($project->organization_id !== $user->active_organization_id) {
            throw ValidationException::withMessages([
                'project' => 'This project does not belong to your active organization.',
            ]);
        }

        $user->active_project_id = $project->id;
        $user->active_environment_id = null;
        $user->save();
    }
}
