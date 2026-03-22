<?php

namespace App\Actions\Dashboard;

use App\Models\AlertRule;
use App\Models\AlertState;

class BuildActiveAlertsSummary
{
    /**
     * Get triggered alerts for org/project/env scope.
     *
     * @return array<string, mixed>
     */
    public function handle(int $organizationId, int $projectId, int $environmentId): array
    {
        $triggeredRuleIds = AlertState::where('status', 'triggered')
            ->pluck('alert_rule_id');

        $triggeredAlerts = AlertRule::query()
            ->whereIn('id', $triggeredRuleIds)
            ->where('organization_id', $organizationId)
            ->where('project_id', $projectId)
            ->where('environment_id', $environmentId)
            ->where('enabled', true)
            ->select(['id', 'name', 'metric', 'operator', 'threshold'])
            ->get()
            ->map(fn ($rule) => [
                'id' => $rule->id,
                'name' => $rule->name,
                'metric' => $rule->metric,
                'condition' => "{$rule->operator} {$rule->threshold}",
            ])
            ->values()
            ->all();

        return [
            'count' => count($triggeredAlerts),
            'alerts' => $triggeredAlerts,
        ];
    }
}
