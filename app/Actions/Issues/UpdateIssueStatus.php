<?php

namespace App\Actions\Issues;

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
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'open' => ['resolved', 'ignored'],
        'resolved' => ['open'],
        'ignored' => ['open'],
    ];

    /**
     * Update the status of an issue.
     *
     * @throws ValidationException
     */
    public function handle(Issue $issue, string $newStatus, User $actor): Issue
    {
        $currentStatus = $issue->status;
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition issue from '{$currentStatus}' to '{$newStatus}'.",
            ]);
        }

        $issue->update(['status' => $newStatus]);

        IssueActivity::create([
            'issue_id' => $issue->id,
            'actor_id' => $actor->id,
            'type' => 'status_changed',
            'metadata' => ['from' => $currentStatus, 'to' => $newStatus],
            'created_at' => now(),
        ]);

        IssueStatusChanged::dispatch($issue, $currentStatus, $newStatus, $actor);

        return $issue;
    }
}
