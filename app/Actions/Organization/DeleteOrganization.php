<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\User;

class DeleteOrganization
{
    /**
     * Soft-delete an organization.
     */
    public function handle(Organization $org, User $actor): void
    {
        $org->delete();
    }
}
