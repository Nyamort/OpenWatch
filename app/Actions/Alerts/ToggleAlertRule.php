<?php

namespace App\Actions\Alerts;

use App\Models\AlertRule;
use App\Models\AlertState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ToggleAlertRule
{
    /**
     * Toggle the enabled state of an alert rule.
     *
     * @throws ValidationException
     */
    public function handle(AlertRule $rule): AlertRule
    {
        if (! $rule->enabled) {
            $this->enable($rule);
        } else {
            $this->disable($rule);
        }

        $this->invalidateCache($rule->project_id, $rule->environment_id);

        return $rule->refresh();
    }

    /**
     * Enable the alert rule, checking that it has recipients.
     *
     * @throws ValidationException
     */
    private function enable(AlertRule $rule): void
    {
        if ($rule->recipients()->count() === 0) {
            throw ValidationException::withMessages([
                'enabled' => 'Cannot enable an alert rule with no recipients.',
            ]);
        }

        $rule->update(['enabled' => true]);
    }

    /**
     * Disable the alert rule and remove its alert state.
     */
    private function disable(AlertRule $rule): void
    {
        $rule->update(['enabled' => false]);

        AlertState::query()
            ->where('alert_rule_id', $rule->id)
            ->delete();
    }

    /**
     * Invalidate the alert rules cache for the given project and environment.
     */
    private function invalidateCache(int $projectId, int $environmentId): void
    {
        Cache::forget("alert_rules:{$projectId}:{$environmentId}");
    }
}
