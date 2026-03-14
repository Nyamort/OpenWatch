<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserNotificationPreference>
 */
class UserNotificationPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => fake()->randomElement([
                UserNotificationPreference::CATEGORY_ISSUE_UPDATES,
                UserNotificationPreference::CATEGORY_THRESHOLD_ALERTS,
                UserNotificationPreference::CATEGORY_SECURITY,
            ]),
            'enabled' => true,
        ];
    }

    /**
     * Indicate the preference is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}
