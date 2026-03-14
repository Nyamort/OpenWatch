<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\AcceptInvitation;
use App\Actions\Organization\InviteMember;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationInvitationController extends Controller
{
    /**
     * Show the invitation acceptance page.
     */
    public function show(string $token): Response
    {
        $tokenHash = hash('sha256', $token);

        $invitation = OrganizationInvitation::query()
            ->where('token_hash', $tokenHash)
            ->with(['organization', 'role'])
            ->firstOrFail();

        return Inertia::render('organizations/invitations/accept', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(Request $request, string $token, AcceptInvitation $action): RedirectResponse
    {
        $tokenHash = hash('sha256', $token);

        $invitation = OrganizationInvitation::query()
            ->where('token_hash', $tokenHash)
            ->firstOrFail();

        $action->handle($invitation, $request->user());

        return to_route('organizations.show', $invitation->organization_id);
    }

    /**
     * Invite a new member to the organization.
     */
    public function store(Request $request, Organization $organization, InviteMember $action): RedirectResponse
    {
        $requestingMember = $request->attributes->get('organization_member');
        $ownerRole = $organization->roles()->where('slug', 'owner')->first();
        $adminRole = $organization->roles()->where('slug', 'admin')->first();

        $isOwnerOrAdmin = $requestingMember !== null
            && in_array($requestingMember->organization_role_id, array_filter([
                $ownerRole?->id,
                $adminRole?->id,
            ]), true);

        if (! $isOwnerOrAdmin) {
            abort(403, 'Only owners and admins can invite members.');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'organization_role_id' => ['required', 'integer', 'exists:organization_roles,id'],
        ]);

        $action->handle($organization, $request->user(), $data);

        return to_route('organizations.members.index', $organization);
    }

    /**
     * Revoke a pending invitation.
     */
    public function destroy(Request $request, Organization $organization, OrganizationInvitation $invitation): RedirectResponse
    {
        $requestingMember = $request->attributes->get('organization_member');
        $ownerRole = $organization->roles()->where('slug', 'owner')->first();
        $adminRole = $organization->roles()->where('slug', 'admin')->first();

        $isOwnerOrAdmin = $requestingMember !== null
            && in_array($requestingMember->organization_role_id, array_filter([
                $ownerRole?->id,
                $adminRole?->id,
            ]), true);

        if (! $isOwnerOrAdmin) {
            abort(403, 'Only owners and admins can revoke invitations.');
        }

        $invitation->delete();

        return to_route('organizations.members.index', $organization);
    }
}
