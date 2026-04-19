import { router } from '@inertiajs/react';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { marked } from 'marked';
import { useState } from 'react';
import IssueCommentController from '@/actions/App/Http/Controllers/Issues/IssueCommentController';
import { UserAvatar } from '@/components/issues/timeline/user-avatar';
import { Button } from '@/components/ui/button';
import type { CommentEntry, TimelineEntry } from '@/types/timeline';

interface Props {
    entry: TimelineEntry & CommentEntry;
    environmentSlug: string;
    issueId: number;
    canModerate: boolean;
}

export function TimelineCommentItem({
    entry,
    environmentSlug,
    issueId,
    canModerate,
}: Props) {
    const [isEditing, setIsEditing] = useState(false);
    const occurredAt = parseISO(entry.occurred_at);
    const timeAgo = formatDistanceToNow(occurredAt, { addSuffix: true });
    const isDeleted = entry.data.deleted;
    const canDelete = entry.data.can_edit || canModerate;

    function handleDelete() {
        if (!confirm('Delete this comment?')) {
            return;
        }
        const action = IssueCommentController.destroy({
            environment: environmentSlug,
            issue: issueId,
            comment: entry.data.id,
        });
        router.delete(action.url, { preserveScroll: true });
    }

    return (
        <li className="relative pl-10">
            <span className="absolute left-2.5 top-3 size-3 -translate-y-1/2 rounded-full border-2 border-background bg-muted-foreground" />
            <div className="flex items-start gap-3">
                <UserAvatar user={entry.actor} size="sm" />
                <div className="flex-1 overflow-hidden rounded-lg border bg-card">
                    <div className="flex items-center justify-between gap-2 border-b bg-muted/30 px-4 py-2 text-sm">
                        <div className="flex items-center gap-2">
                            <span className="font-medium text-foreground">
                                {entry.actor?.name ?? 'Unknown'}
                            </span>
                            <span className="text-muted-foreground">
                                commented
                            </span>
                            <span className="text-muted-foreground/60">·</span>
                            <time
                                dateTime={entry.occurred_at}
                                title={occurredAt.toLocaleString()}
                                className="text-muted-foreground"
                            >
                                {timeAgo}
                            </time>
                            {entry.data.edited_at && !isDeleted && (
                                <span className="text-xs text-muted-foreground/80">
                                    (edited)
                                </span>
                            )}
                        </div>
                        {!isDeleted && !isEditing && (
                            <div className="flex items-center gap-1">
                                {entry.data.can_edit && (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => setIsEditing(true)}
                                    >
                                        Edit
                                    </Button>
                                )}
                                {canDelete && (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        className="text-destructive hover:text-destructive"
                                        onClick={handleDelete}
                                    >
                                        Delete
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                    {isDeleted ? (
                        <p className="px-4 py-3 text-sm italic text-muted-foreground">
                            This comment was deleted.
                        </p>
                    ) : isEditing ? (
                        <EditForm
                            environmentSlug={environmentSlug}
                            issueId={issueId}
                            commentId={entry.data.id}
                            initialBody={entry.data.body ?? ''}
                            onCancel={() => setIsEditing(false)}
                        />
                    ) : (
                        <div
                            className="prose prose-sm max-w-none px-4 py-3 dark:prose-invert"
                            dangerouslySetInnerHTML={{
                                __html: marked(entry.data.body ?? '') as string,
                            }}
                        />
                    )}
                </div>
            </div>
        </li>
    );
}

function EditForm({
    environmentSlug,
    issueId,
    commentId,
    initialBody,
    onCancel,
}: {
    environmentSlug: string;
    issueId: number;
    commentId: number;
    initialBody: string;
    onCancel: () => void;
}) {
    const [body, setBody] = useState(initialBody);
    const [processing, setProcessing] = useState(false);

    function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        const action = IssueCommentController.update({
            environment: environmentSlug,
            issue: issueId,
            comment: commentId,
        });
        setProcessing(true);
        router.patch(
            action.url,
            { body },
            {
                preserveScroll: true,
                onSuccess: () => onCancel(),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-2 px-4 py-3">
            <textarea
                value={body}
                onChange={(e) => setBody(e.target.value)}
                className="min-h-[80px] w-full resize-y rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                disabled={processing}
            />
            <div className="flex justify-end gap-2">
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    onClick={onCancel}
                    disabled={processing}
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    size="sm"
                    disabled={!body.trim() || processing}
                >
                    {processing ? 'Saving...' : 'Save'}
                </Button>
            </div>
        </form>
    );
}
