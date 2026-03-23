<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Organization\UpdateMemberRole;
use App\Actions\Organization\UpdateOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateMemberRoleRequest;
use App\Http\Requests\Settings\UpdateOrganizationSettingsRequest;
use App\Models\Organization;
use App\Models\OrganizationAuditEvent;
use App\Models\OrganizationMember;
use App\Services\Authorization\PermissionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSettingsController extends Controller
{
    public function __construct(private readonly PermissionResolver $permissionResolver) {}

    public function general(Organization $organization): Response
    {
        return Inertia::render('settings/organizations/general', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'timezone' => $organization->timezone,
                'logo_url' => $organization->getFirstMediaUrl('logo'),
            ],
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(UpdateOrganizationSettingsRequest $request, Organization $organization, UpdateOrganization $action): RedirectResponse
    {
        $this->authorize('update', $organization);

        $action->handle($organization, $request->validated());

        if ($request->hasFile('logo')) {
            $organization->addMediaFromRequest('logo')->toMediaCollection('logo');
        } elseif ($request->boolean('remove_logo')) {
            $organization->clearMediaCollection('logo');
        }

        return to_route('settings.organizations.general', $organization);
    }

    public function members(Request $request, Organization $organization): Response
    {
        $members = $organization->members()
            ->with(['user:id,name,email', 'role:id,name,slug'])
            ->get();

        $roles = $organization->roles()->select('id', 'name', 'slug')->get();

        $currentMemberId = $organization->members()
            ->where('user_id', $request->user()->id)
            ->value('id');

        return Inertia::render('settings/organizations/members', [
            'organization' => $organization,
            'members' => $members,
            'roles' => $roles,
            'currentMemberId' => $currentMemberId,
        ]);
    }

    public function updateMemberRole(UpdateMemberRoleRequest $request, Organization $organization, OrganizationMember $member, UpdateMemberRole $action): RedirectResponse
    {
        $requestingUserId = $request->user()->id;
        $requesterRole = $this->permissionResolver->getRole($requestingUserId, $organization->id);

        if (! in_array($requesterRole, ['owner', 'admin'], true)) {
            abort(403);
        }

        $newRole = $organization->roles()->findOrFail($request->validated()['role_id']);

        $action->handle($organization, $member, $newRole);

        return to_route('settings.organizations.members', $organization);
    }

    public function destroyMember(Request $request, Organization $organization, OrganizationMember $member): RedirectResponse
    {
        $requestingUserId = $request->user()->id;
        $requesterRole = $this->permissionResolver->getRole($requestingUserId, $organization->id);

        if (! in_array($requesterRole, ['owner', 'admin'], true)) {
            abort(403);
        }

        if ($member->user_id === $requestingUserId) {
            abort(403, 'You cannot remove yourself from the organization.');
        }

        app(\App\Actions\Organization\RemoveMember::class)->handle($organization, $member);

        return to_route('settings.organizations.members', $organization);
    }

    public function audit(Request $request, Organization $organization): Response
    {
        $userId = $request->user()->id;
        $role = $this->permissionResolver->getRole($userId, $organization->id);

        if (! in_array($role, ['owner', 'admin'], true)) {
            abort(403);
        }

        $query = OrganizationAuditEvent::query()
            ->where('organization_id', $organization->id)
            ->latest('created_at');

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->input('actor_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $events = $query->paginate(50)->withQueryString();

        return Inertia::render('settings/organizations/audit', [
            'organization' => $organization,
            'events' => $events,
            'filters' => $request->only(['event_type', 'actor_id', 'date_from', 'date_to']),
        ]);
    }
}
