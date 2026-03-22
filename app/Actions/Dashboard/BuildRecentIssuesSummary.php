<?php

namespace App\Actions\Dashboard;

use App\Models\Issue;

class BuildRecentIssuesSummary
{
    /**
     * Get last 5 open issues ordered by last_seen_at desc.
     *
     * @return array<string, mixed>
     */
    public function handle(int $organizationId, int $projectId, int $environmentId): array
    {
        $issues = Issue::query()
            ->where('organization_id', $organizationId)
            ->where('project_id', $projectId)
            ->where('environment_id', $environmentId)
            ->where('status', 'open')
            ->orderByDesc('last_seen_at')
            ->limit(5)
            ->select(['id', 'title', 'type', 'priority', 'occurrence_count', 'last_seen_at'])
            ->get()
            ->map(fn ($issue) => [
                'id' => $issue->id,
                'title' => $issue->title,
                'type' => $issue->type,
                'priority' => $issue->priority,
                'occurrence_count' => $issue->occurrence_count,
                'last_seen_at' => $issue->last_seen_at,
            ])
            ->values()
            ->all();

        return [
            'count' => count($issues),
            'issues' => $issues,
        ];
    }
}
