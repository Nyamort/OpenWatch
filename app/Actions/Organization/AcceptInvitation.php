<?php

namespace App\Actions\Organization;

use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInvitation
{
    /**
     * Accept a pending organization invitation.
     *
     * @throws ValidationException
     */
    public function handle(OrganizationInvitation $invitation, User $user): OrganizationMember
    {
        if ($invitation->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'invitation' => 'This invitation has expired.',
            ]);
        }

        if ($invitation->accepted_at !== null) {
            throw ValidationException::withMessages([
                'invitation' => 'This invitation has already been accepted.',
            ]);
        }

        return DB::transaction(function () use ($invitation, $user): OrganizationMember {
            $member = OrganizationMember::create([
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
                'organization_role_id' => $invitation->organization_role_id,
            ]);

            $invitation->update([
                'accepted_at' => now(),
                'accepted_by_user_id' => $user->id,
            ]);

            if ($user->active_organization_id === null) {
                $user->active_organization_id = $invitation->organization_id;
                $user->save();
            }

            return $member;
        });
    }
}
