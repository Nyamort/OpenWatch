<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationRole>
 */
class OrganizationRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Owner', 'Admin', 'Developer', 'Viewer']);

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => strtolower($name),
            'is_default' => false,
            'permissions' => ['projects.view', 'members.view'],
        ];
    }

    /**
     * Indicate this role is the owner role.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Owner',
            'slug' => 'owner',
            'is_default' => false,
            'permissions' => ['billing.manage', 'members.invite', 'members.remove', 'members.view', 'projects.create', 'projects.delete', 'projects.view'],
        ]);
    }

    /**
     * Indicate this role is the developer role (default).
     */
    public function developer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Developer',
            'slug' => 'developer',
            'is_default' => true,
            'permissions' => ['projects.create', 'projects.view', 'environments.view'],
        ]);
    }
}
