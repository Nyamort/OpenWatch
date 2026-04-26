import { router, useForm, usePage } from '@inertiajs/react';
import { marked } from 'marked';
import { ArrowRight, CircleCheck, CircleDot, CircleMinus, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { update as commentUpdate, destroy as commentDestroy } from '@/actions/App/Http/Controllers/Issues/IssueCommentController';
import { store } from '@/actions/App/Http/Controllers/Issues/IssueCommentController';
import { update } from '@/actions/App/Http/Controllers/Issues/IssueController';
import { MarkdownEditor } from '@/components/issues/markdown-editor';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import type { Auth } from '@/types';

interface Actor {
    name: string;
    email: string;
}

interface BaseEntry {
    id: string;
    actor: Actor | null;
    created_at: string;
}

interface CreatedEntry extends BaseEntry {
    kind: 'created';
}

interface StatusChangedEntry extends BaseEntry {
    kind: 'status_changed';
    from: string;
    to: string;
}

interface AssignedEntry extends BaseEntry {
    kind: 'assigned';
    from_user: Actor | null;
    to_user: Actor | null;
}

interface PriorityChangedEntry extends BaseEntry {
    kind: 'priority_changed';
    from: string | null;
    to: string;
}

interface CommentedEntry extends BaseEntry {
    kind: 'commented';
    comment_id: number;
    body: string;
    edited_at: string | null;
}

interface StatusWithCommentEntry extends BaseEntry {
    kind: 'status_updated_with_comment' | 'status_update_comment_updated' | 'status_update_comment_deleted';
    new_status: string;
    comment_id?: number;
    body?: string;
    edited_at?: string | null;
}

export type TimelineEntry =
    | CreatedEntry
    | StatusChangedEntry
    | AssignedEntry
    | PriorityChangedEntry
    | CommentedEntry
    | StatusWithCommentEntry;

interface Props {
    timeline: TimelineEntry[];
    environment: { slug: string };
    issue: { id: number; status: string };
}

const COMMENT_KINDS = new Set([
    'commented',
    'status_updated_with_comment',
    'status_update_comment_updated',
    'status_update_comment_deleted',
]);

function isCommentEntry(entry: TimelineEntry): entry is CommentedEntry | StatusWithCommentEntry {
    return COMMENT_KINDS.has(entry.kind);
}

function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

function relativeTime(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const minutes = Math.floor(diff / 60_000);
    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;
    return new Date(dateStr).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

const STATUS_LABELS: Record<string, string> = { open: 'Open', resolved: 'Resolved', ignored: 'Ignored' };
const STATUS_VERBS: Record<string, string> = { open: 'reopened', resolved: 'resolved', ignored: 'ignored' };
const PRIORITY_LABELS: Record<string, string> = { none: 'None', low: 'Low', medium: 'Medium', high: 'High' };

function actionText(entry: TimelineEntry): string {
    switch (entry.kind) {
        case 'created':
            return 'created the issue';
        case 'status_changed':
            return `changed status to ${STATUS_LABELS[entry.to] ?? entry.to}`;
        case 'priority_changed':
            return entry.to === 'none'
                ? 'removed the priority'
                : `changed priority to ${PRIORITY_LABELS[entry.to] ?? entry.to}`;
        case 'assigned':
            if (!entry.to_user) return 'unassigned the issue';
            return `assigned the issue to ${entry.to_user.name}`;
        case 'commented':
            return 'added a comment';
        case 'status_updated_with_comment':
        case 'status_update_comment_updated':
        case 'status_update_comment_deleted':
            return `added a comment and ${STATUS_VERBS[entry.new_status] ?? entry.new_status} the issue`;
    }
}

function StatusIcon({ status }: { status: string }) {
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

function TimelineEntryItem({
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
    const hasBody =
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
                {isOwnComment && !editing && (
                    <div className="flex items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
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
            </div>

            {hasBody && !editing && (
                <div className="mt-2 ml-8">
                    <div
                        className="prose prose-sm max-w-none rounded-lg border bg-neutral-50 px-4 py-3 dark:bg-white/2 dark:prose-invert"
                        dangerouslySetInnerHTML={{ __html: marked(entry.body ?? '') as string }}
                    />
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

export function ActivityFeed({ timeline, environment, issue }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currentUserEmail = auth.user.email;

    const { data, setData, post, processing, reset, errors } = useForm({ body: '' });

    const [showResolveDialog, setShowResolveDialog] = useState(false);
    const [resolveComment, setResolveComment] = useState('');

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(store.url({ environment, issue: issue.id }), {
            onSuccess: () => reset('body'),
        });
    }

    function confirmResolve() {
        router.patch(
            update.url({ environment, issue: issue.id }),
            { status: 'resolved', ...(resolveComment.trim() ? { comment: resolveComment.trim() } : {}) },
            { preserveScroll: true },
        );
        setShowResolveDialog(false);
        setResolveComment('');
    }

    return (
        <>
            <Card className="gap-0 overflow-hidden bg-surface py-0">
                <CardHeader className="border-b px-5 py-3">
                    <CardTitle className="text-sm font-semibold">Activity</CardTitle>
                </CardHeader>

                <CardContent className="flex flex-col p-0">
                    <div className="px-5 py-4">
                        {timeline.length === 0 ? (
                            <p className="py-2 text-center text-sm text-muted-foreground">No activity yet.</p>
                        ) : (
                            <div className="relative flex flex-col gap-4">
                                <div className="absolute top-3 bottom-3 left-3 z-0 w-px border-l border-dashed border-neutral-300 dark:border-neutral-700/50" />
                                {timeline.map((entry) => (
                                    <TimelineEntryItem
                                        key={entry.id}
                                        entry={entry}
                                        environment={environment}
                                        issue={issue}
                                        currentUserEmail={currentUserEmail}
                                    />
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="border-t px-5 py-4">
                        <form onSubmit={submit} className="space-y-3">
                            <MarkdownEditor
                                value={data.body}
                                onChange={(value) => setData('body', value)}
                                placeholder="Leave a comment…"
                            />
                            {errors.body && <p className="text-sm text-destructive">{errors.body}</p>}
                            <div className="flex items-center justify-end gap-2">
                                {issue.status !== 'resolved' && (
                                    <button
                                        type="button"
                                        onClick={() => setShowResolveDialog(true)}
                                        className="rounded-lg border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
                                    >
                                        Resolve now
                                    </button>
                                )}
                                <button
                                    type="submit"
                                    disabled={processing || !data.body.trim()}
                                    className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity disabled:opacity-50"
                                >
                                    {processing ? 'Commenting…' : 'Comment'}
                                </button>
                            </div>
                        </form>
                    </div>
                </CardContent>
            </Card>

            <Dialog open={showResolveDialog} onOpenChange={setShowResolveDialog}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Resolve issue</DialogTitle>
                        <DialogDescription asChild>
                            <div className="flex items-center gap-2 pt-1">
                                <span className="flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium">
                                    <StatusIcon status={issue.status} />
                                    {STATUS_LABELS[issue.status] ?? issue.status}
                                </span>
                                <ArrowRight className="size-3.5 shrink-0 text-muted-foreground/60" />
                                <span className="flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium">
                                    <StatusIcon status="resolved" />
                                    Resolved
                                </span>
                            </div>
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-1.5">
                        <Textarea
                            autoFocus
                            placeholder="Describe what fixed this issue…"
                            value={resolveComment}
                            onChange={(e) => setResolveComment(e.target.value)}
                            onKeyDown={(e) => {
                                if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                                    e.preventDefault();
                                    confirmResolve();
                                }
                            }}
                            rows={4}
                            className="resize-none"
                        />
                        <p className="select-none text-right text-xs text-muted-foreground/60">
                            <kbd className="font-sans">⌘</kbd> + <kbd className="font-sans">↵</kbd> to confirm
                        </p>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowResolveDialog(false)}>
                            Cancel
                        </Button>
                        <Button onClick={confirmResolve} className="bg-green-600 text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600">
                            Resolve issue
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
