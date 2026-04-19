<?php

namespace App\Models;

use App\Contracts\Timeline\TimelineEventable;
use App\Enums\TimelineEventKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class IssueAssignmentEvent extends Model implements TimelineEventable
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'from_user_id',
        'to_user_id',
    ];

    public function entry(): MorphOne
    {
        return $this->morphOne(IssueTimelineEntry::class, 'eventable');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function eventKind(): TimelineEventKind
    {
        return TimelineEventKind::AssignmentChanged;
    }

    public function toTimelineData(?User $viewer): array
    {
        return [
            'from_user' => $this->fromUser
                ? ['id' => $this->fromUser->id, 'name' => $this->fromUser->name, 'email' => $this->fromUser->email]
                : null,
            'to_user' => $this->toUser
                ? ['id' => $this->toUser->id, 'name' => $this->toUser->name, 'email' => $this->toUser->email]
                : null,
        ];
    }

    public function timelineEagerLoads(): array
    {
        return ['fromUser:id,name,email', 'toUser:id,name,email'];
    }
}
