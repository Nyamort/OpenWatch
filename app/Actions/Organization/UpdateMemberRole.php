<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationRole;
use App\Services\Authorization\PermissionResolver;
use Illuminate\Validation\ValidationException;

class UpdateMemberRole
{
    public function __construct(private readonly PermissionResolver $permissionResolver) {}

    /**
     * Update the role of an organization member.
     *
     * @throws ValidationException
     */
    public function handle(Organization $org, OrganizationMember $member, OrganizationRole $newRole): void
    {
        $ownerRole = $org->roles()->where('slug', 'owner')->first();

        if ($ownerRole && $member->organization_role_id === $ownerRole->id) {
            $ownerCount = $org->members()->where('organization_role_id', $ownerRole->id)->count();

            if ($ownerCount <= 1 && $newRole->slug !== 'owner') {
                throw ValidationException::withMessages([
                    'role' => 'Cannot demote the last owner of the organization.',
                ]);
            }
        }

        $member->update(['organization_role_id' => $newRole->id]);

        $this->permissionResolver->invalidate($member->user_id, $org->id);
    }
}
