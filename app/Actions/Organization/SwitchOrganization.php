<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SwitchOrganization
{
    /**
     * Switch the user's active organization.
     *
     * @throws ValidationException
     */
    public function handle(User $user, Organization $org): void
    {
        $isMember = OrganizationMember::query()
            ->where('organization_id', $org->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'organization' => 'You are not a member of this organization.',
            ]);
        }

        $user->active_organization_id = $org->id;
        $user->save();
    }
}
