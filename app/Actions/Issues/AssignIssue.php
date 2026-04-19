<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Models\IssueAssignmentEvent;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignIssue
{
    public function __construct(
        private readonly RecordIssueTimelineEvent $recordTimelineEvent,
    ) {}

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

        if ($previousAssigneeId === $assigneeId) {
            return $issue;
        }

        return DB::transaction(function () use ($issue, $actor, $previousAssigneeId, $assigneeId): Issue {
            $issue->update(['assignee_id' => $assigneeId]);

            $this->recordTimelineEvent->handle(
                $issue,
                $actor,
                new IssueAssignmentEvent([
                    'from_user_id' => $previousAssigneeId,
                    'to_user_id' => $assigneeId,
                ]),
            );

            return $issue;
        });
    }
}
