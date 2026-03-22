<?php

namespace App\Actions\Alerts;

use App\Models\AlertRule;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAlertRule
{
    /**
     * Update an existing alert rule and sync recipients.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(Organization $organization, AlertRule $rule, array $data): AlertRule
    {
        $this->validateRecipients($organization, $data['recipient_ids'] ?? []);

        return DB::transaction(function () use ($rule, $data): AlertRule {
            $rule->update([
                'name' => $data['name'],
                'metric' => $data['metric'],
                'operator' => $data['operator'],
                'threshold' => $data['threshold'],
                'window_minutes' => $data['window_minutes'],
            ]);

            $rule->recipients()->delete();

            foreach ($data['recipient_ids'] as $userId) {
                $rule->recipients()->create(['user_id' => $userId]);
            }

            $this->invalidateCache($rule->project_id, $rule->environment_id);

            return $rule->refresh();
        });
    }

    /**
     * Validate that all recipient IDs are members of the organization.
     *
     * @param  list<int>  $recipientIds
     *
     * @throws ValidationException
     */
    private function validateRecipients(Organization $organization, array $recipientIds): void
    {
        if (empty($recipientIds)) {
            throw ValidationException::withMessages([
                'recipient_ids' => 'At least one recipient is required.',
            ]);
        }

        $memberCount = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->whereIn('user_id', $recipientIds)
            ->count();

        if ($memberCount !== count($recipientIds)) {
            throw ValidationException::withMessages([
                'recipient_ids' => 'One or more recipients are not members of this organization.',
            ]);
        }
    }

    /**
     * Invalidate the alert rules cache for the given project and environment.
     */
    private function invalidateCache(int $projectId, int $environmentId): void
    {
        Cache::forget("alert_rules:{$projectId}:{$environmentId}");
    }
}
