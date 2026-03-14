<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Validation\ValidationException;

class RemoveMember
{
    /**
     * Remove a member from the organization.
     *
     * @throws ValidationException
     */
    public function handle(Organization $org, OrganizationMember $member): void
    {
        $ownerRole = $org->roles()->where('slug', 'owner')->first();

        if ($ownerRole === null) {
            $member->delete();

            return;
        }

        $isOwner = $member->organization_role_id === $ownerRole->id;

        if ($isOwner) {
            $ownerCount = $org->members()->where('organization_role_id', $ownerRole->id)->count();

            if ($ownerCount <= 1) {
                throw ValidationException::withMessages([
                    'member' => 'Cannot remove the last owner of the organization.',
                ]);
            }
        }

        $member->delete();
    }
}
