<?php

namespace App\Actions\Issues;

use App\Enums\IssueType;
use App\Models\Issue;
use App\Models\User;
use App\Services\ClickHouse\ClickHouseService;
use Illuminate\Support\Collection;

class BuildIssueDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Build the issue detail data including sources, activities, comments, and timeline.
     *
     * @return array<string, mixed>
     */
    public function handle(Issue $issue): array
    {
        $issue->load([
            'sources',
            'assignee:id,name,email',
        ]);

        $activities = $issue->activities()
            ->with('actor:id,name,email')
            ->oldest('created_at')
            ->get();

        $comments = $issue->comments()
            ->with('author:id,name,email')
            ->oldest()
            ->get();

        $timeline = $this->buildTimeline($activities, $comments);

        $exceptionSummary = null;

        if ($issue->type === IssueType::Exception) {
            $groupKey = $issue->sources->first(fn ($s) => $s->group_key !== null)?->group_key;

            if ($groupKey !== null) {
                $envId = $issue->environment_id;
                $escapedKey = ClickHouseService::escape($groupKey);

                $exceptionSummary = $this->clickhouse->selectOne("
                    SELECT *
                    FROM extraction_exceptions
                    WHERE environment_id = {$envId}
                      AND group_key = {$escapedKey}
                    ORDER BY recorded_at DESC
                    LIMIT 1
                ");
            }
        }

        return [
            'issue' => $issue,
            'timeline' => $timeline,
            'exception_summary' => $exceptionSummary,
        ];
    }

    /**
     * Merge activities and comments into a single chronological timeline.
     *
     * @param  Collection<int, \App\Models\IssueActivity>  $activities
     * @param  Collection<int, \App\Models\IssueComment>  $comments
     * @return list<array<string, mixed>>
     */
    private function buildTimeline(Collection $activities, Collection $comments): array
    {
        $commentsById = $comments->keyBy('id');

        $assignedUserIds = $activities
            ->where('type', 'assigned')
            ->flatMap(fn ($a) => [$a->metadata['from'] ?? null, $a->metadata['to'] ?? null])
            ->filter()
            ->unique()
            ->values();

        $assignedUsers = $assignedUserIds->isNotEmpty()
            ? User::query()->select(['id', 'name', 'email'])->whereIn('id', $assignedUserIds)->get()->keyBy('id')
            : collect();

        $entries = $activities->map(function ($activity) use ($commentsById, $assignedUsers) {
            $entry = [
                'id' => 'activity-'.$activity->id,
                'kind' => $activity->type,
                'actor' => $activity->actor ? ['name' => $activity->actor->name, 'email' => $activity->actor->email] : null,
                'created_at' => $activity->created_at,
            ];

            if ($activity->type === 'commented') {
                $commentId = $activity->metadata['comment_id'] ?? null;
                $comment = $commentId ? $commentsById->get($commentId) : null;
                if ($comment) {
                    $entry['comment_id'] = $comment->id;
                    $entry['body'] = $comment->body;
                    $entry['edited_at'] = $comment->edited_at;
                    $entry['actor'] = ['name' => $comment->author->name, 'email' => $comment->author->email];
                }
            } elseif (in_array($activity->type, ['status_updated_with_comment', 'status_update_comment_updated', 'status_update_comment_deleted'])) {
                $entry['new_status'] = $activity->metadata['to'] ?? null;
                $commentId = $activity->metadata['comment_id'] ?? null;
                $comment = $commentId ? $commentsById->get($commentId) : null;
                if ($comment) {
                    $entry['comment_id'] = $comment->id;
                    $entry['body'] = $comment->body;
                    $entry['edited_at'] = $comment->edited_at;
                }
            } elseif ($activity->type === 'status_changed') {
                $entry['from'] = $activity->metadata['from'] ?? null;
                $entry['to'] = $activity->metadata['to'] ?? null;
            } elseif ($activity->type === 'priority_changed') {
                $entry['from'] = $activity->metadata['from'] ?? null;
                $entry['to'] = $activity->metadata['to'] ?? null;
            } elseif ($activity->type === 'assigned') {
                $fromId = $activity->metadata['from'] ?? null;
                $toId = $activity->metadata['to'] ?? null;
                $fromUser = $fromId ? $assignedUsers->get($fromId) : null;
                $toUser = $toId ? $assignedUsers->get($toId) : null;
                $entry['from_user'] = $fromUser ? ['name' => $fromUser->name, 'email' => $fromUser->email] : null;
                $entry['to_user'] = $toUser ? ['name' => $toUser->name, 'email' => $toUser->email] : null;
            }

            return $entry;
        });

        return $entries->values()->all();
    }
}
