<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use Illuminate\Validation\ValidationException;

class UpdateOrganization
{
    /**
     * Update an organization's settings.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(Organization $org, array $data): Organization
    {
        if (isset($data['slug']) && $data['slug'] !== $org->slug) {
            if (Organization::query()->where('slug', $data['slug'])->exists()) {
                throw ValidationException::withMessages([
                    'slug' => 'The slug has already been taken.',
                ]);
            }
        }

        $org->update(array_filter([
            'name' => $data['name'] ?? $org->name,
            'slug' => $data['slug'] ?? $org->slug,
            'logo_url' => array_key_exists('logo_url', $data) ? $data['logo_url'] : $org->logo_url,
        ], fn (mixed $v): bool => $v !== null));

        return $org->fresh();
    }
}
