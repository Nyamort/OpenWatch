import { router } from '@inertiajs/react';
import { CircleCheck, CircleDot, CircleMinus, Pencil, Trash2 } from 'lucide-react';
import { marked } from 'marked';
import { useState } from 'react';
import { update as commentUpdate, destroy as commentDestroy } from '@/actions/App/Http/Controllers/Issues/IssueCommentController';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { type Actor, type CommentedEntry, type TimelineEntry, actionText, initials, isCommentEntry, relativeTime } from './activity-feed-types';

export function StatusIcon({ status }: { status: string }) {
    if (status === 'resolved') return <CircleCheck className="size-3.5 text-green-500" />;
    if (status === 'ignored') return <CircleMinus className="size-3.5 text-muted-foreground" />;
    return <CircleDot className="size-3.5 text-blue-500" />;
}

function ActorAvatar({ actor }: { actor: Actor | null }) {
    if (!actor) {
        return (
            <div className="flex size-6 shrink-0 items-center justify-center rounded-full bg-blue-200/50 dark:bg-blue-950/50">
                <span className="size-2 rounded-full bg-blue-600" />
            </div>
        );
    }
    return (
        <Avatar className="size-6 shrink-0 rounded-full">
            <AvatarFallback className="text-[10px]">{initials(actor.name)}</AvatarFallback>
        </Avatar>
    );
}

function EventDot() {
    return (
        <div className="flex size-6 shrink-0 items-center justify-center">
            <span className="size-2 rounded-full bg-border" />
        </div>
    );
}

export function TimelineEntryItem({
    entry,
    environment,
    issue,
    currentUserEmail,
}: {
    entry: TimelineEntry;
    environment: { slug: string };
    issue: { id: number };
    currentUserEmail: string;
}) {
    const [editing, setEditing] = useState(false);
    const [editBody, setEditBody] = useState('');

    const actor = entry.actor;
    const actorName = actor?.name ?? 'Openwatch';

    const commentId =
        entry.kind === 'commented' || entry.kind === 'status_updated_with_comment' || entry.kind === 'status_update_comment_updated'
            ? entry.comment_id
            : undefined;

    const isOwnComment = !!actor && actor.email === currentUserEmail && commentId !== undefined;
    const isEdited =
        (entry.kind === 'commented' || entry.kind === 'status_update_comment_updated') && !!entry.edited_at;
    const isDeleted =
        entry.kind === 'status_update_comment_deleted' ||
        (entry.kind === 'commented' && !entry.comment_id);
    const hasBody =
        isDeleted ||
        (entry.kind === 'commented' && !!entry.body) ||
        ((entry.kind === 'status_updated_with_comment' || entry.kind === 'status_update_comment_updated') && !!entry.body);

    function startEdit() {
        setEditBody((entry as CommentedEntry).body ?? '');
        setEditing(true);
    }

    function saveEdit() {
        if (!commentId) return;
        router.patch(
            commentUpdate.url({ environment, issue: issue.id, comment: commentId }),
            { body: editBody },
            { preserveScroll: true, only: ['timeline'], onSuccess: () => setEditing(false) },
        );
    }

    function deleteComment() {
        if (!commentId) return;
        router.delete(commentDestroy.url({ environment, issue: issue.id, comment: commentId }), {
            preserveScroll: true,
            only: ['timeline'],
        });
    }

    if (!isCommentEntry(entry)) {
        return (
            <div className="flex items-center gap-2 text-sm">
                <EventDot />
                <p className="flex-1 text-muted-foreground">
                    <span className="font-medium text-foreground">{actorName}</span> {actionText(entry)}
                </p>
                <time
                    dateTime={entry.created_at}
                    className="shrink-0 text-xs text-muted-foreground/50"
                    title={new Date(entry.created_at).toLocaleString()}
                >
                    {relativeTime(entry.created_at)}
                </time>
            </div>
        );
    }

    return (
        <div className="group">
            <div className="flex items-center gap-2 text-sm">
                <ActorAvatar actor={actor} />
                <span className="font-medium">{actorName}</span>
                <span className="font-light text-muted-foreground">{actionText(entry)}</span>
                {isEdited && <span className="text-xs text-muted-foreground/50 italic">(edited)</span>}
                <time
                    dateTime={entry.created_at}
                    className="ml-auto shrink-0 text-xs text-muted-foreground/50"
                    title={new Date(entry.created_at).toLocaleString()}
                >
                    {relativeTime(entry.created_at)}
                </time>
            </div>

            {hasBody && !editing && (
                <div className="relative mt-2 ml-8">
                    {isOwnComment && (
                        <div className="absolute top-2 right-2 flex items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                            <button
                                onClick={startEdit}
                                className="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                            >
                                <Pencil className="size-3" />
                            </button>
                            <button
                                onClick={deleteComment}
                                className="rounded p-1 text-muted-foreground hover:bg-muted hover:text-destructive"
                            >
                                <Trash2 className="size-3" />
                            </button>
                        </div>
                    )}
                    {isDeleted ? (
                        <div className="rounded-lg border bg-neutral-50 px-4 py-3 dark:bg-white/2">
                            <p className="text-sm text-muted-foreground/60 italic">This comment was deleted.</p>
                        </div>
                    ) : (
                        <div
                            className="prose prose-sm max-w-none rounded-lg border bg-neutral-50 px-4 py-3 dark:bg-white/2 dark:prose-invert"
                            dangerouslySetInnerHTML={{ __html: marked(entry.body ?? '') as string }}
                        />
                    )}
                </div>
            )}

            {editing && (
                <div className="mt-2 ml-8 flex flex-col gap-2">
                    <Textarea
                        autoFocus
                        value={editBody}
                        onChange={(e) => setEditBody(e.target.value)}
                        rows={3}
                        className="resize-none"
                    />
                    <div className="flex gap-2">
                        <Button size="sm" onClick={saveEdit} disabled={!editBody.trim()}>
                            Save
                        </Button>
                        <Button size="sm" variant="outline" onClick={() => setEditing(false)}>
                            Cancel
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
