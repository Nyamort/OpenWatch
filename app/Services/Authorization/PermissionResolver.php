<?php

namespace App\Services\Authorization;

use App\Models\OrganizationMember;
use Illuminate\Support\Facades\Cache;

class PermissionResolver
{
    private const TTL = 300;

    /**
     * Get the role slug for a user within an organization.
     */
    public function getRole(int $userId, int $organizationId): ?string
    {
        $cacheKey = $this->cacheKey($userId, $organizationId);

        return Cache::remember($cacheKey, self::TTL, function () use ($userId, $organizationId): ?string {
            $member = OrganizationMember::query()
                ->where('user_id', $userId)
                ->where('organization_id', $organizationId)
                ->with('role')
                ->first();

            return $member?->role?->slug;
        });
    }

    /**
     * Determine if a user can perform an ability within an organization.
     */
    public function can(int $userId, int $organizationId, string $ability): bool
    {
        $role = $this->getRole($userId, $organizationId);

        if ($role === null) {
            return false;
        }

        return match ($role) {
            'owner' => true,
            'admin' => $ability !== 'delete_organization',
            'developer' => $this->developerCan($ability),
            'viewer' => str_starts_with($ability, 'view'),
            default => false,
        };
    }

    /**
     * Invalidate the cached role for a user in an organization.
     */
    public function invalidate(int $userId, int $organizationId): void
    {
        Cache::forget($this->cacheKey($userId, $organizationId));
    }

    /**
     * Determine if a developer can perform the given ability.
     */
    private function developerCan(string $ability): bool
    {
        if (in_array($ability, ['manage_members', 'manage_roles'], true)) {
            return false;
        }

        if (str_starts_with($ability, 'delete_')) {
            return false;
        }

        return in_array($ability, ['read', 'write', 'create', 'view'], true)
            || str_starts_with($ability, 'view')
            || str_starts_with($ability, 'read')
            || str_starts_with($ability, 'write')
            || str_starts_with($ability, 'create');
    }

    /**
     * Build the cache key for a user/org combination.
     */
    private function cacheKey(int $userId, int $organizationId): string
    {
        return "permissions:{$userId}:{$organizationId}";
    }
}
