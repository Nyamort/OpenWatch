<?php

namespace App\Actions\Project;

use App\Models\Environment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SwitchEnvironment
{
    /**
     * Switch the user's active environment.
     *
     * @throws ValidationException
     */
    public function handle(User $user, Environment $environment): void
    {
        if ($environment->project_id !== $user->active_project_id) {
            throw ValidationException::withMessages([
                'environment' => 'This environment does not belong to your active project.',
            ]);
        }

        $user->active_environment_id = $environment->id;
        $user->save();
    }
}
