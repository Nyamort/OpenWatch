<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Any member can view the organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $this->isMember($user, $organization);
    }

    /**
     * Owner or admin can update the organization.
     */
    public function update(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['owner', 'admin']);
    }

    /**
     * Only owner can delete the organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['owner']);
    }

    /**
     * Owner or admin can manage members.
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['owner', 'admin']);
    }

    /**
     * Only owner can manage roles.
     */
    public function manageRoles(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['owner']);
    }

    /**
     * Check if the user is a member of the organization.
     */
    private function isMember(User $user, Organization $organization): bool
    {
        return OrganizationMember::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->exists();
    }

    /**
     * Check if the user has one of the given role slugs.
     *
     * @param  list<string>  $roles
     */
    private function hasRole(User $user, Organization $organization, array $roles): bool
    {
        return OrganizationMember::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->whereHas('role', fn ($q) => $q->whereIn('slug', $roles))
            ->exists();
    }
}
