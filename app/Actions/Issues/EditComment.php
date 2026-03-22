<?php

namespace App\Actions\Issues;

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

        return $comment;
    }
}
