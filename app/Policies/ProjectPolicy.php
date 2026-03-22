<?php

namespace App\Policies;

use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Any member can view a project.
     */
    public function view(User $user, Project $project): bool
    {
        return $this->isMember($user, $project->organization_id);
    }

    /**
     * Developer or above can create projects.
     */
    public function create(User $user, Project $project): bool
    {
        return $this->hasRole($user, $project->organization_id, ['owner', 'admin', 'developer']);
    }

    /**
     * Developer or above can update projects.
     */
    public function update(User $user, Project $project): bool
    {
        return $this->hasRole($user, $project->organization_id, ['owner', 'admin', 'developer']);
    }

    /**
     * Admin or above can delete projects.
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->hasRole($user, $project->organization_id, ['owner', 'admin']);
    }

    /**
     * Check if the user is a member of the given organization.
     */
    private function isMember(User $user, int $organizationId): bool
    {
        return OrganizationMember::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->exists();
    }

    /**
     * Check if the user has one of the given role slugs in the organization.
     *
     * @param  list<string>  $roles
     */
    private function hasRole(User $user, int $organizationId, array $roles): bool
    {
        return OrganizationMember::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->whereHas('role', fn ($q) => $q->whereIn('slug', $roles))
            ->exists();
    }
}
