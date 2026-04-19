<?php

namespace App\Http\Resources\Issues;

use App\Contracts\Timeline\TimelineEventable;
use App\Models\IssueTimelineEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IssueTimelineEntry
 */
class TimelineEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TimelineEventable $eventable */
        $eventable = $this->eventable;
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'kind' => $eventable->eventKind()->value,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'actor' => $this->actor
                ? ['id' => $this->actor->id, 'name' => $this->actor->name, 'email' => $this->actor->email]
                : null,
            'data' => $eventable->toTimelineData($viewer),
        ];
    }
}
