<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InviteMember
{
    /**
     * Invite a member to the organization by email.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Organization $org, User $inviter, array $data): OrganizationInvitation
    {
        $token = Str::random(32);
        $tokenHash = hash('sha256', $token);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $org->id,
            'organization_role_id' => $data['organization_role_id'],
            'invited_by_user_id' => $inviter->id,
            'accepted_by_user_id' => null,
            'email' => $data['email'],
            'name' => $data['name'] ?? null,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ]);

        Notification::route('mail', $data['email'])
            ->notify(new OrganizationInvitationNotification($invitation, $token, $org));

        return $invitation;
    }
}
