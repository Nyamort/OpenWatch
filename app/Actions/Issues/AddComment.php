<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddComment
{
    public function __construct(
        private readonly RecordIssueTimelineEvent $recordTimelineEvent,
    ) {}

    /**
     * Add a comment to an issue.
     *
     * @throws ValidationException
     */
    public function handle(Issue $issue, string $body, User $author): IssueComment
    {
        $body = trim($body);

        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => 'Comment body cannot be empty.',
            ]);
        }

        return DB::transaction(function () use ($issue, $body, $author): IssueComment {
            $comment = IssueComment::create([
                'issue_id' => $issue->id,
                'author_id' => $author->id,
                'body' => $body,
            ]);

            $this->recordTimelineEvent->handle($issue, $author, $comment);

            return $comment;
        });
    }
}
