<?php

namespace App\Actions\Issues;

use App\Enums\IssueStatus;
use App\Events\IssueStatusChanged;
use App\Models\Issue;
use App\Models\IssueStatusChangeEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        IssueStatus::Resolved->value => [IssueStatus::Open],
        IssueStatus::Ignored->value => [IssueStatus::Open],
    ];

    public function __construct(
        private readonly RecordIssueTimelineEvent $recordTimelineEvent,
    ) {}

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

        DB::transaction(function () use ($issue, $currentStatus, $newStatus, $actor): void {
            $issue->update(['status' => $newStatus]);

            $this->recordTimelineEvent->handle(
                $issue,
                $actor,
                new IssueStatusChangeEvent([
                    'from_status' => $currentStatus,
                    'to_status' => $newStatus,
                ]),
            );
        });

        IssueStatusChanged::dispatch($issue, $currentStatus->value, $newStatus->value, $actor);

        return $issue;
    }
}
