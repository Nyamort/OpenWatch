<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationAuditEvent;
use App\Services\Authorization\PermissionResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuditController extends Controller
{
    public function __construct(private readonly PermissionResolver $permissionResolver) {}

    /**
     * Display the audit log for the given organization.
     */
    public function index(Request $request, Organization $organization): Response
    {
        $userId = $request->user()->id;

        if (! $this->permissionResolver->can($userId, $organization->id, 'view_audit')) {
            $role = $this->permissionResolver->getRole($userId, $organization->id);

            if (! in_array($role, ['owner', 'admin'], true)) {
                abort(SymfonyResponse::HTTP_FORBIDDEN);
            }
        }

        // Re-check: only owner/admin can access audit log
        $role = $this->permissionResolver->getRole($userId, $organization->id);

        if (! in_array($role, ['owner', 'admin'], true)) {
            abort(SymfonyResponse::HTTP_FORBIDDEN);
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

        return Inertia::render('organizations/audit', [
            'organization' => $organization,
            'events' => $events,
            'filters' => $request->only(['event_type', 'actor_id', 'date_from', 'date_to']),
        ]);
    }
}
