<?php

namespace App\Models;

use App\Contracts\Timeline\TimelineEventable;
use App\Enums\IssueStatus;
use App\Enums\TimelineEventKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class IssueStatusChangeEvent extends Model implements TimelineEventable
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'from_status',
        'to_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => IssueStatus::class,
            'to_status' => IssueStatus::class,
        ];
    }

    public function entry(): MorphOne
    {
        return $this->morphOne(IssueTimelineEntry::class, 'eventable');
    }

    public function eventKind(): TimelineEventKind
    {
        return TimelineEventKind::StatusChanged;
    }

    public function toTimelineData(?User $viewer): array
    {
        return [
            'from' => $this->from_status->value,
            'to' => $this->to_status->value,
        ];
    }

    public function timelineEagerLoads(): array
    {
        return [];
    }
}
