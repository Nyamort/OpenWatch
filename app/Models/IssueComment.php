<?php

namespace App\Models;

use App\Contracts\Timeline\TimelineEventable;
use App\Enums\TimelineEventKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssueComment extends Model implements TimelineEventable
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'issue_id',
        'author_id',
        'body',
        'edited_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function entry(): MorphOne
    {
        return $this->morphOne(IssueTimelineEntry::class, 'eventable');
    }

    public function eventKind(): TimelineEventKind
    {
        return TimelineEventKind::Commented;
    }

    public function toTimelineData(?User $viewer): array
    {
        $isDeleted = $this->trashed();

        return [
            'id' => $this->id,
            'body' => $isDeleted ? null : $this->body,
            'edited_at' => $this->edited_at?->toIso8601String(),
            'deleted' => $isDeleted,
            'can_edit' => ! $isDeleted && $viewer !== null && $viewer->id === $this->author_id,
        ];
    }

    public function timelineEagerLoads(): array
    {
        return [];
    }
}
