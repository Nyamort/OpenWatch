<?php

namespace App\Policies;

use App\Models\AlertRule;
use App\Models\OrganizationMember;
use App\Models\User;

class AlertRulePolicy
{
    /**
     * Any member can view alert rules.
     */
    public function view(User $user, AlertRule $alertRule): bool
    {
        return $this->isMember($user, $alertRule->organization_id);
    }

    /**
     * Developer or above can create alert rules.
     */
    public function create(User $user, AlertRule $alertRule): bool
    {
        return $this->hasRole($user, $alertRule->organization_id, ['owner', 'admin', 'developer']);
    }

    /**
     * Developer or above can update alert rules.
     */
    public function update(User $user, AlertRule $alertRule): bool
    {
        return $this->hasRole($user, $alertRule->organization_id, ['owner', 'admin', 'developer']);
    }

    /**
     * Admin or above can delete alert rules.
     */
    public function delete(User $user, AlertRule $alertRule): bool
    {
        return $this->hasRole($user, $alertRule->organization_id, ['owner', 'admin']);
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
