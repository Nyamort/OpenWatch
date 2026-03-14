<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Support\Facades\DB;

class TransferOwnership
{
    /**
     * Transfer ownership from one member to another within the organization.
     */
    public function handle(Organization $org, OrganizationMember $currentOwner, OrganizationMember $newOwner): void
    {
        $ownerRole = $org->roles()->where('slug', 'owner')->firstOrFail();
        $adminRole = $org->roles()->where('slug', 'admin')->firstOrFail();

        DB::transaction(function () use ($ownerRole, $adminRole, $currentOwner, $newOwner): void {
            $newOwner->update(['organization_role_id' => $ownerRole->id]);
            $currentOwner->update(['organization_role_id' => $adminRole->id]);
        });
    }
}
