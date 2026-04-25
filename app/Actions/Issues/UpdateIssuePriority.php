<?php

namespace App\Actions\Issues;

use App\Enums\IssuePriority;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\User;

class UpdateIssuePriority
{
    /**
     * Update the priority of an issue.
     */
    public function handle(Issue $issue, IssuePriority $newPriority, User $actor): Issue
    {
        $previousPriority = $issue->priority;

        $issue->update(['priority' => $newPriority]);

        IssueActivity::create([
            'issue_id' => $issue->id,
            'actor_id' => $actor->id,
            'type' => 'priority_changed',
            'metadata' => ['from' => $previousPriority?->value, 'to' => $newPriority->value],
            'created_at' => now(),
        ]);

        return $issue;
    }
}
