<?php

namespace App\Actions\Alerts;

use App\Models\AlertRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DeleteAlertRule
{
    /**
     * Delete an alert rule after confirming the rule name.
     *
     * @throws ValidationException
     */
    public function handle(AlertRule $rule, string $confirmation): void
    {
        if ($confirmation !== $rule->name) {
            throw ValidationException::withMessages([
                'confirmation' => 'The confirmation does not match the rule name.',
            ]);
        }

        $projectId = $rule->project_id;
        $environmentId = $rule->environment_id;

        $rule->delete();

        $this->invalidateCache($projectId, $environmentId);
    }

    /**
     * Invalidate the alert rules cache for the given project and environment.
     */
    private function invalidateCache(int $projectId, int $environmentId): void
    {
        Cache::forget("alert_rules:{$projectId}:{$environmentId}");
    }
}
