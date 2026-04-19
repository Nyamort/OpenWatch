<?php

namespace App\Actions\Issues;

use App\Http\Resources\Issues\TimelineEntryResource;
use App\Models\Issue;
use App\Timeline\TimelineEventRegistry;

class BuildIssueDetailData
{
    public function __construct(
        private readonly TimelineEventRegistry $timelineRegistry,
    ) {}

    /**
     * Build the issue detail data including sources and unified timeline.
     *
     * @return array{issue: Issue, timeline: \Illuminate\Http\Resources\Json\AnonymousResourceCollection}
     */
    public function handle(Issue $issue): array
    {
        $issue->load([
            'sources',
            'assignee:id,name,email',
        ]);

        $morphWith = [];
        foreach ($this->timelineRegistry->eventableClasses() as $class) {
            $morphWith[$class] = (new $class)->timelineEagerLoads();
        }

        $timeline = $issue->timeline()
            ->with([
                'actor:id,name,email',
                'eventable' => fn ($morphTo) => $morphTo->morphWith($morphWith),
            ])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get()
            ->filter(fn ($entry) => $entry->eventable !== null)
            ->values();

        return [
            'issue' => $issue,
            'timeline' => TimelineEntryResource::collection($timeline),
        ];
    }
}
