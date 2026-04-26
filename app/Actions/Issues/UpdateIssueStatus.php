<?php

namespace App\Actions\Issues;

use App\Enums\IssueStatus;
use App\Events\IssueStatusChanged;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateIssueStatus
{
    /**
     * Allowed status transitions.
     *
     * @var array<string, list<IssueStatus>>
     */
    private const ALLOWED_TRANSITIONS = [
        IssueStatus::Open->value => [IssueStatus::Resolved, IssueStatus::Ignored],
        IssueStatus::Resolved->value => [IssueStatus::Open, IssueStatus::Ignored],
        IssueStatus::Ignored->value => [IssueStatus::Open, IssueStatus::Resolved],
    ];

    /**
     * Update the status of an issue.
     *
     * @throws ValidationException
     */
    public function handle(Issue $issue, IssueStatus $newStatus, User $actor): Issue
    {
        $currentStatus = $issue->status;
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus->value] ?? [];

        if (! in_array($newStatus, $allowed, strict: true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition issue from '{$currentStatus->value}' to '{$newStatus->value}'.",
            ]);
        }

        $issue->update(['status' => $newStatus]);

        IssueActivity::create([
            'issue_id' => $issue->id,
            'actor_id' => $actor->id,
            'type' => 'status_changed',
            'metadata' => ['from' => $currentStatus->value, 'to' => $newStatus->value],
            'created_at' => now(),
        ]);

        IssueStatusChanged::dispatch($issue, $currentStatus->value, $newStatus->value, $actor);

        return $issue;
    }
}
