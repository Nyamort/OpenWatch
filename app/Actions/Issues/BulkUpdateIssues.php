<?php

namespace App\Actions\Issues;

use App\Models\Environment;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;

class BulkUpdateIssues
{
    public function __construct(
        private readonly UpdateIssueStatus $updateIssueStatus,
    ) {}

    /**
     * Bulk update issues within the caller's org/project/env scope.
     *
     * @param  list<int>  $issueIds
     * @return array{processed: int, skipped: int}
     */
    public function handle(
        Organization $organization,
        Project $project,
        Environment $environment,
        array $issueIds,
        string $action,
        User $actor,
    ): array {
        $newStatus = match ($action) {
            'resolve' => 'resolved',
            'ignore' => 'ignored',
            'reopen' => 'open',
            default => throw new \InvalidArgumentException("Invalid bulk action: {$action}"),
        };

        $issues = Issue::query()
            ->whereIn('id', $issueIds)
            ->where('organization_id', $organization->id)
            ->where('project_id', $project->id)
            ->where('environment_id', $environment->id)
            ->get();

        $processed = 0;
        $skipped = count($issueIds) - $issues->count();

        foreach ($issues as $issue) {
            try {
                $this->updateIssueStatus->handle($issue, $newStatus, $actor);
                $processed++;
            } catch (\Illuminate\Validation\ValidationException) {
                $skipped++;
            }
        }

        return ['processed' => $processed, 'skipped' => $skipped];
    }
}
