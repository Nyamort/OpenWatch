<?php

namespace App\Actions\Alerts;

use App\Models\AlertRule;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAlertRule
{
    /**
     * Allowed metric identifiers.
     *
     * @var list<string>
     */
    public const ALLOWED_METRICS = [
        'error_rate',
        'exception_count',
        'request_count',
        'job_failure_rate',
        'p95_duration',
    ];

    /**
     * Allowed comparison operators.
     *
     * @var list<string>
     */
    public const ALLOWED_OPERATORS = ['>', '>=', '<', '<='];

    /**
     * Allowed time window values in minutes.
     *
     * @var list<int>
     */
    public const ALLOWED_WINDOWS = [5, 15, 30, 60, 120, 240, 1440];

    /**
     * Create a new alert rule with recipients.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(Organization $organization, Project $project, Environment $environment, array $data): AlertRule
    {
        $this->validateRecipients($organization, $data['recipient_ids'] ?? []);

        return DB::transaction(function () use ($organization, $project, $environment, $data): AlertRule {
            $rule = AlertRule::create([
                'organization_id' => $organization->id,
                'project_id' => $project->id,
                'environment_id' => $environment->id,
                'name' => $data['name'],
                'metric' => $data['metric'],
                'operator' => $data['operator'],
                'threshold' => $data['threshold'],
                'window_minutes' => $data['window_minutes'],
                'enabled' => true,
            ]);

            foreach ($data['recipient_ids'] as $userId) {
                $rule->recipients()->create(['user_id' => $userId]);
            }

            $this->invalidateCache($project->id, $environment->id);

            return $rule;
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
