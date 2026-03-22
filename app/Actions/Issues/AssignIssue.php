<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignIssue
{
    /**
     * Assign an issue to a user who is a member of the organization.
     *
     * @throws ValidationException
     */
    public function handle(Issue $issue, ?int $assigneeId, User $actor): Issue
    {
        if ($assigneeId !== null) {
            $isMember = OrganizationMember::query()
                ->where('organization_id', $issue->organization_id)
                ->where('user_id', $assigneeId)
                ->exists();

            if (! $isMember) {
                throw ValidationException::withMessages([
                    'assignee_id' => 'The assignee must be a member of this organization.',
                ]);
            }
        }

        $previousAssigneeId = $issue->assignee_id;
        $issue->update(['assignee_id' => $assigneeId]);

        IssueActivity::create([
            'issue_id' => $issue->id,
            'actor_id' => $actor->id,
            'type' => 'assigned',
            'metadata' => ['from' => $previousAssigneeId, 'to' => $assigneeId],
            'created_at' => now(),
        ]);

        return $issue;
    }
}
