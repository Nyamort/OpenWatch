<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationInvitation>
 */
class OrganizationInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = Str::random(32);

        return [
            'organization_id' => Organization::factory(),
            'organization_role_id' => OrganizationRole::factory(),
            'invited_by_user_id' => User::factory(),
            'accepted_by_user_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    /**
     * Indicate the invitation has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate the invitation has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'accepted_at' => now()->subHour(),
            'accepted_by_user_id' => User::factory(),
        ]);
    }
}
