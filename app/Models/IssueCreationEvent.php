<?php

namespace App\Models;

use App\Contracts\Timeline\TimelineEventable;
use App\Enums\TimelineEventKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class IssueCreationEvent extends Model implements TimelineEventable
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [];

    public function entry(): MorphOne
    {
        return $this->morphOne(IssueTimelineEntry::class, 'eventable');
    }

    public function eventKind(): TimelineEventKind
    {
        return TimelineEventKind::IssueCreated;
    }

    public function toTimelineData(?User $viewer): array
    {
        return [];
    }

    public function timelineEagerLoads(): array
    {
        return [];
    }
}
