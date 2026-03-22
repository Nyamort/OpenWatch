<?php

namespace App\Services\Organization;

use App\Contracts\QuotaStatus;
use App\Models\Organization;

class QuotaService
{
    /**
     * Check the quota status for a given resource within an organization.
     *
     * Resources: 'members', 'projects'
     * Returns 'ok' if the organization has no plan (unlimited).
     */
    public function check(string $resource, Organization $organization): QuotaStatus
    {
        $plan = $organization->plan;

        if ($plan === null) {
            $current = $this->current($resource, $organization);

            return new QuotaStatus('ok', $current, null);
        }

        $limit = $this->limit($resource, $plan);
        $current = $this->current($resource, $organization);

        if ($limit === null) {
            return new QuotaStatus('ok', $current, null);
        }

        if ($current >= $limit) {
            return new QuotaStatus('exceeded', $current, $limit);
        }

        $warnThreshold = $plan->warn_threshold_pct ?? 80;

        if ($current >= (int) round($limit * $warnThreshold / 100)) {
            return new QuotaStatus('warning', $current, $limit);
        }

        return new QuotaStatus('ok', $current, $limit);
    }

    /**
     * Get the limit for a given resource from the plan.
     */
    private function limit(string $resource, \App\Models\OrganizationPlan $plan): ?int
    {
        return match ($resource) {
            'members' => $plan->max_members,
            'projects' => $plan->max_projects,
            default => null,
        };
    }

    /**
     * Get the current count for a given resource in the organization.
     */
    private function current(string $resource, Organization $organization): int
    {
        return match ($resource) {
            'members' => $organization->members()->count(),
            'projects' => $organization->projects()->count(),
            default => 0,
        };
    }
}
