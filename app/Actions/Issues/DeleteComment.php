<?php

namespace App\Actions\Issues;

use App\Models\IssueComment;
use App\Models\User;
use App\Services\Authorization\PermissionResolver;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteComment
{
    public function __construct(
        private readonly PermissionResolver $permissionResolver,
    ) {}

    /**
     * Delete a comment (author or admin/owner).
     *
     * @throws AuthorizationException
     */
    public function handle(IssueComment $comment, User $actor): void
    {
        $isAuthor = $comment->author_id === $actor->id;

        if (! $isAuthor) {
            $role = $this->permissionResolver->getRole($actor->id, $comment->issue->organization_id);
            $canDelete = in_array($role, ['owner', 'admin'], true);

            if (! $canDelete) {
                throw new AuthorizationException('You do not have permission to delete this comment.');
            }
        }

        $comment->delete();
    }
}
