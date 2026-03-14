<?php

namespace App\Actions\Settings;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Validation\ValidationException;

class UpdateNotificationPreferences
{
    /**
     * Update the user's notification preferences by category.
     *
     * @param  array<string, bool>  $categories
     */
    public function handle(User $user, array $categories): void
    {
        foreach ($categories as $category => $enabled) {
            if (in_array($category, UserNotificationPreference::LOCKED_CATEGORIES, true) && ! $enabled) {
                throw ValidationException::withMessages([
                    'categories' => ["The '{$category}' notification category cannot be disabled."],
                ]);
            }

            UserNotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'category' => $category],
                ['enabled' => $enabled],
            );
        }
    }
}
