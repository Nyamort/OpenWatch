<?php

namespace App\Contracts\Timeline;

use App\Enums\TimelineEventKind;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface TimelineEventable
{
    /**
     * Relation to the timeline entry that references this eventable.
     */
    public function entry(): MorphOne;

    /**
     * The canonical kind this eventable represents.
     */
    public function eventKind(): TimelineEventKind;

    /**
     * Serialize the kind-specific payload for the given viewer.
     *
     * @return array<string, mixed>
     */
    public function toTimelineData(?User $viewer): array;

    /**
     * The relationships that must be eager-loaded when fetching
     * this eventable through a timeline query.
     *
     * @return list<string>
     */
    public function timelineEagerLoads(): array;
}
