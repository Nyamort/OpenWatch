<?php

namespace App\Actions\Issues;

use App\Models\IssueActivity;
use App\Models\IssueComment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class EditComment
{
    /**
     * Edit a comment (author only).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(IssueComment $comment, string $body, User $editor): IssueComment
    {
        if ($comment->author_id !== $editor->id) {
            throw new AuthorizationException('Only the comment author can edit this comment.');
        }

        $body = trim($body);

        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => 'Comment body cannot be empty.',
            ]);
        }

        $comment->update([
            'body' => $body,
            'edited_at' => now(),
        ]);

        IssueActivity::query()
            ->where('issue_id', $comment->issue_id)
            ->whereIn('type', ['status_updated_with_comment', 'status_update_comment_updated'])
            ->whereJsonContains('metadata->comment_id', $comment->id)
            ->update(['type' => 'status_update_comment_updated']);

        return $comment;
    }
}
