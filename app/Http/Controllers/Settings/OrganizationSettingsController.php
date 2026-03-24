<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Organization\InviteMember;
use App\Actions\Organization\UpdateMemberRole;
use App\Actions\Organization\UpdateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\RotateToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\InviteMemberRequest;
use App\Http\Requests\Settings\StoreEnvironmentRequest;
use App\Http\Requests\Settings\UpdateApplicationRequest;
use App\Http\Requests\Settings\UpdateEnvironmentRequest;
use App\Http\Requests\Settings\UpdateMemberRoleRequest;
use App\Http\Requests\Settings\UpdateOrganizationSettingsRequest;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\OrganizationAuditEvent;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\Project;
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

        $pendingInvitations = $organization->invitations()
            ->pending()
            ->with('role:id,name,slug')
            ->get(['id', 'name', 'email', 'organization_role_id', 'expires_at']);

        $roles = $organization->roles()->select('id', 'name', 'slug')->get();

        $currentMemberId = $organization->members()
            ->where('user_id', $request->user()->id)
            ->value('id');

        return Inertia::render('settings/organizations/members', [
            'organization' => $organization,
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
            'roles' => $roles,
            'currentMemberId' => $currentMemberId,
        ]);
    }

    public function storeInvitation(InviteMemberRequest $request, Organization $organization, InviteMember $action): RedirectResponse
    {
        $requesterRole = $this->permissionResolver->getRole($request->user()->id, $organization->id);

        if (! in_array($requesterRole, ['owner', 'admin'], true)) {
            abort(403);
        }

        $action->handle($organization, $request->user(), $request->validated());

        return to_route('settings.organizations.members', $organization);
    }

    public function destroyInvitation(Request $request, Organization $organization, OrganizationInvitation $invitation): RedirectResponse
    {
        $requesterRole = $this->permissionResolver->getRole($request->user()->id, $organization->id);

        if (! in_array($requesterRole, ['owner', 'admin'], true)) {
            abort(403);
        }

        $invitation->delete();

        return to_route('settings.organizations.members', $organization);
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

    public function applications(Organization $organization): Response
    {
        $projects = $organization->projects()
            ->withCount('environments')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);

        return Inertia::render('settings/organizations/applications', [
            'organization' => $organization,
            'projects' => $projects,
        ]);
    }

    public function editApplication(Organization $organization, Project $project): Response
    {
        $environments = $project->environments()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color', 'url']);

        return Inertia::render('settings/organizations/application', [
            'organization' => $organization,
            'project' => array_merge($project->only('id', 'name', 'slug', 'description'), [
                'logo_url' => $project->getFirstMediaUrl('logo'),
            ]),
            'environments' => $environments,
            'newToken' => session('environment_token'),
            'newTokenEnvironmentName' => session('environment_token_name'),
        ]);
    }

    public function updateApplication(UpdateApplicationRequest $request, Organization $organization, Project $project): RedirectResponse
    {
        $project->update($request->safe()->only('name', 'description'));

        if ($request->hasFile('logo')) {
            $project->addMediaFromRequest('logo')->toMediaCollection('logo');
        } elseif ($request->boolean('remove_logo')) {
            $project->clearMediaCollection('logo');
        }

        return to_route('settings.organizations.applications.edit', [$organization, $project]);
    }

    public function destroyApplication(Request $request, Organization $organization, Project $project): RedirectResponse
    {
        $requesterRole = $this->permissionResolver->getRole($request->user()->id, $organization->id);

        if (! in_array($requesterRole, ['owner', 'admin'], true)) {
            abort(403);
        }

        $project->delete();

        return to_route('settings.organizations.applications', $organization);
    }

    public function storeEnvironment(StoreEnvironmentRequest $request, Organization $organization, Project $project, CreateEnvironment $createEnvironment): RedirectResponse
    {
        $result = $createEnvironment->handle($project, $request->validated());

        session()->flash('environment_token', $result->token);
        session()->flash('environment_token_name', $result->environment->name);

        return to_route('settings.organizations.applications.edit', [$organization, $project]);
    }

    public function rotateEnvironmentToken(Request $request, Organization $organization, Project $project, Environment $environment, RotateToken $rotateToken): RedirectResponse
    {
        $requesterRole = $this->permissionResolver->getRole($request->user()->id, $organization->id);

        if (! in_array($requesterRole, ['owner', 'admin'], true)) {
            abort(403);
        }

        $activeToken = $environment->projectTokens()->where('status', 'active')->firstOrFail();

        ['token' => $rawToken] = $rotateToken->handle($activeToken);

        session()->flash('environment_token', $rawToken);
        session()->flash('environment_token_name', $environment->name);

        return to_route('settings.organizations.applications.edit', [$organization, $project]);
    }

    public function updateEnvironment(UpdateEnvironmentRequest $request, Organization $organization, Project $project, Environment $environment): RedirectResponse
    {
        $environment->update($request->validated());

        return to_route('settings.organizations.applications.edit', [$organization, $project]);
    }

    public function destroyEnvironment(Request $request, Organization $organization, Project $project, Environment $environment): RedirectResponse
    {
        $requesterRole = $this->permissionResolver->getRole($request->user()->id, $organization->id);

        if (! in_array($requesterRole, ['owner', 'admin'], true)) {
            abort(403);
        }

        $environment->delete();

        return to_route('settings.organizations.applications.edit', [$organization, $project]);
    }
}
