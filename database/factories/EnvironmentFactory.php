<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Environment>
 */
class EnvironmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Production', 'Staging', 'Development', 'Custom']);

        return [
            'project_id' => Project::factory(),
            'name' => $name,
            'slug' => strtolower($name).'-'.fake()->unique()->randomNumber(4),
            'status' => 'active',
            'archived_at' => null,
            'last_ingested_at' => null,
            'health_status' => fake()->randomElement(['healthy', 'degraded', 'inactive']),
        ];
    }

    /**
     * Indicate the environment is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'archived_at' => now(),
        ]);
    }
}
