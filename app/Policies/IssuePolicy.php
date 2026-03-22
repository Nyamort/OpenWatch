<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\OrganizationMember;
use App\Models\User;

class IssuePolicy
{
    /**
     * Any member can view issues.
     */
    public function view(User $user, Issue $issue): bool
    {
        return $this->isMember($user, $issue->organization_id);
    }

    /**
     * Developer or above can create issues.
     */
    public function create(User $user, Issue $issue): bool
    {
        return $this->hasRole($user, $issue->organization_id, ['owner', 'admin', 'developer']);
    }

    /**
     * Developer or above can update issues.
     */
    public function update(User $user, Issue $issue): bool
    {
        return $this->hasRole($user, $issue->organization_id, ['owner', 'admin', 'developer']);
    }

    /**
     * Admin or above can delete issues.
     */
    public function delete(User $user, Issue $issue): bool
    {
        return $this->hasRole($user, $issue->organization_id, ['owner', 'admin']);
    }

    /**
     * Developer or above can add comments.
     */
    public function comment(User $user, Issue $issue): bool
    {
        return $this->hasRole($user, $issue->organization_id, ['owner', 'admin', 'developer']);
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
