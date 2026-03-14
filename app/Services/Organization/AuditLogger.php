<?php

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\OrganizationAuditEvent;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    /**
     * Log an audit event for the given organization.
     *
     * @param  array<string, mixed>  $extra
     */
    public function log(Organization $org, string $eventType, ?User $actor = null, array $extra = []): OrganizationAuditEvent
    {
        return OrganizationAuditEvent::create([
            'organization_id' => $org->id,
            'actor_id' => $actor?->id,
            'event_type' => $eventType,
            'target_type' => $extra['target_type'] ?? null,
            'target_id' => $extra['target_id'] ?? null,
            'metadata' => $extra['metadata'] ?? [],
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
