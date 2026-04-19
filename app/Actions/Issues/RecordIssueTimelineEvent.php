<?php

namespace App\Actions\Issues;

use App\Contracts\Timeline\TimelineEventable;
use App\Models\Issue;
use App\Models\IssueTimelineEntry;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class RecordIssueTimelineEvent
{
    /**
     * Persist a new timeline entry backed by the given eventable.
     *
     * If the eventable has not been persisted yet, it is saved first
     * inside the same transaction so the timeline entry cannot be
     * orphaned.
     */
    public function handle(
        Issue $issue,
        ?User $actor,
        TimelineEventable $eventable,
        ?DateTimeInterface $occurredAt = null,
    ): IssueTimelineEntry {
        return DB::transaction(function () use ($issue, $actor, $eventable, $occurredAt): IssueTimelineEntry {
            if (! $eventable->exists) {
                $eventable->save();
            }

            return IssueTimelineEntry::create([
                'issue_id' => $issue->id,
                'actor_id' => $actor?->id,
                'eventable_type' => $eventable->getMorphClass(),
                'eventable_id' => $eventable->getKey(),
                'occurred_at' => $occurredAt ?? now(),
            ]);
        });
    }
}
