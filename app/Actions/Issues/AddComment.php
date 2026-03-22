<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\IssueComment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AddComment
{
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

        $comment = IssueComment::create([
            'issue_id' => $issue->id,
            'author_id' => $author->id,
            'body' => $body,
        ]);

        IssueActivity::create([
            'issue_id' => $issue->id,
            'actor_id' => $author->id,
            'type' => 'commented',
            'metadata' => ['comment_id' => $comment->id],
            'created_at' => now(),
        ]);

        return $comment;
    }
}
