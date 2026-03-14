<?php

namespace Database\Factories;

use App\Models\Environment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectToken>
 */
class ProjectTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        return [
            'environment_id' => Environment::factory(),
            'token_hash' => hash('sha256', $rawToken),
            'status' => 'active',
            'grace_until' => null,
            'rotated_at' => null,
        ];
    }

    /**
     * Indicate the token is deprecated with a grace window.
     */
    public function deprecated(int $graceDays = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'deprecated',
            'grace_until' => now()->addDays($graceDays),
            'rotated_at' => now(),
        ]);
    }

    /**
     * Indicate the token is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'revoked',
        ]);
    }
}
