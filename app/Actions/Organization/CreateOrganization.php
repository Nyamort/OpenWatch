<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrganization
{
    /**
     * Create a new organization and set up default roles and the creator as Owner.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $data): Organization
    {
        if (Organization::query()->where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'The slug has already been taken.',
            ]);
        }

        return DB::transaction(function () use ($user, $data): Organization {
            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'logo_url' => $data['logo_url'] ?? null,
                'timezone' => $data['timezone'] ?? 'UTC',
                'locale' => $data['locale'] ?? 'en',
            ]);

            $roles = $this->createDefaultRoles($organization);

            $organization->members()->create([
                'user_id' => $user->id,
                'organization_role_id' => $roles['owner']->id,
            ]);

            $user->active_organization_id = $organization->id;
            $user->save();

            return $organization;
        });
    }

    /**
     * Create the default roles for a new organization.
     *
     * @return array<string, \App\Models\OrganizationRole>
     */
    private function createDefaultRoles(Organization $organization): array
    {
        $allPermissions = [
            'billing.manage',
            'members.invite',
            'members.remove',
            'members.view',
            'projects.create',
            'projects.delete',
            'projects.view',
            'environments.create',
            'environments.delete',
            'environments.view',
            'analytics.view',
            'settings.manage',
            'roles.manage',
            'invitations.manage',
        ];

        $adminPermissions = array_values(array_filter(
            $allPermissions,
            fn (string $p): bool => $p !== 'billing.manage'
        ));

        $developerPermissions = [
            'projects.create',
            'projects.delete',
            'projects.view',
            'environments.create',
            'environments.delete',
            'environments.view',
            'analytics.view',
            'members.view',
        ];

        $viewerPermissions = [
            'projects.view',
            'environments.view',
            'analytics.view',
            'members.view',
        ];

        $owner = $organization->roles()->create([
            'name' => 'Owner',
            'slug' => 'owner',
            'is_default' => false,
            'permissions' => $allPermissions,
        ]);

        $organization->roles()->create([
            'name' => 'Admin',
            'slug' => 'admin',
            'is_default' => false,
            'permissions' => $adminPermissions,
        ]);

        $organization->roles()->create([
            'name' => 'Developer',
            'slug' => 'developer',
            'is_default' => true,
            'permissions' => $developerPermissions,
        ]);

        $organization->roles()->create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'is_default' => false,
            'permissions' => $viewerPermissions,
        ]);

        return ['owner' => $owner];
    }
}
