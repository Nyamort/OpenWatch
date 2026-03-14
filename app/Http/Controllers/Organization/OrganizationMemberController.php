<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\RemoveMember;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationMemberController extends Controller
{
    /**
     * Display a listing of organization members with their roles.
     */
    public function index(Organization $organization): Response
    {
        $members = $organization->members()
            ->with(['user', 'role'])
            ->get();

        return Inertia::render('organizations/members/index', [
            'organization' => $organization,
            'members' => $members,
        ]);
    }

    /**
     * Remove a member from the organization.
     */
    public function destroy(Request $request, Organization $organization, OrganizationMember $member, RemoveMember $action): RedirectResponse
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
            abort(403, 'Only owners and admins can remove members.');
        }

        $action->handle($organization, $member);

        return to_route('organizations.members.index', $organization);
    }
}
