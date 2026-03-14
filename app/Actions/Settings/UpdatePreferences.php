<?php

namespace App\Actions\Settings;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdatePreferences
{
    /**
     * Update the user's preferences.
     */
    public function handle(User $user, array $data): User
    {
        if (isset($data['timezone']) && ! in_array($data['timezone'], timezone_identifiers_list(), true)) {
            throw ValidationException::withMessages([
                'timezone' => ['The timezone is invalid.'],
            ]);
        }

        $supportedLocales = ['en', 'fr'];

        if (isset($data['locale']) && ! in_array($data['locale'], $supportedLocales, true)) {
            throw ValidationException::withMessages([
                'locale' => ['The locale is not supported.'],
            ]);
        }

        if (isset($data['timezone'])) {
            $user->timezone = $data['timezone'];
        }

        if (isset($data['locale'])) {
            $user->locale = $data['locale'];
        }

        if (isset($data['display_preferences'])) {
            $user->display_preferences = array_merge(
                $user->display_preferences ?? [],
                $data['display_preferences'],
            );
        }

        $user->save();

        return $user;
    }
}
