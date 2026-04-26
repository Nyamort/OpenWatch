import { router, useForm } from '@inertiajs/react';
import { marked } from 'marked';
import { store } from '@/actions/App/Http/Controllers/Issues/IssueCommentController';
import { update } from '@/actions/App/Http/Controllers/Issues/IssueController';
import { MarkdownEditor } from '@/components/issues/markdown-editor';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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

export type TimelineEntry = CreatedEntry | StatusChangedEntry | AssignedEntry | PriorityChangedEntry | CommentedEntry | StatusWithCommentEntry;

interface Props {
    timeline: TimelineEntry[];
    environment: { slug: string };
    issue: { id: number; status: string };
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

function TimelineEntry({ entry }: { entry: TimelineEntry }) {
    const actor = entry.kind === 'commented' ? entry.actor : entry.actor;
    const actorName = actor?.name ?? 'Openwatch';

    return (
        <div className="overflow-x-hidden">
            <div className="flex items-center gap-1.5 text-sm">
                <ActorAvatar actor={actor} />
                <div className="ml-2 leading-6">
                    <span className="text-foreground">{actorName} </span>
                    <span className="font-light text-muted-foreground">{actionText(entry)}</span>
                    <span className="text-muted-foreground/50"> • </span>
                    <time
                        dateTime={entry.created_at}
                        className="font-light text-muted-foreground"
                        title={new Date(entry.created_at).toLocaleString()}
                    >
                        {relativeTime(entry.created_at)}
                    </time>
                    {(entry.kind === 'commented' || entry.kind === 'status_update_comment_updated') && entry.edited_at && (
                        <span className="ml-1 font-light text-muted-foreground/60 italic">(edited)</span>
                    )}
                </div>
            </div>

            {(entry.kind === 'commented' ||
                ((entry.kind === 'status_updated_with_comment' || entry.kind === 'status_update_comment_updated') &&
                    entry.body)) && (
                <div className="mt-2 ml-10">
                    <div
                        className="prose prose-sm max-w-none rounded-lg border bg-neutral-50 px-4 py-3 dark:bg-white/2 dark:prose-invert"
                        dangerouslySetInnerHTML={{ __html: marked(entry.body ?? '') as string }}
                    />
                </div>
            )}
        </div>
    );
}

export function ActivityFeed({ timeline, environment, issue }: Props) {
    const { data, setData, post, processing, reset, errors } = useForm({ body: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(store.url({ environment, issue: issue.id }), {
            onSuccess: () => reset('body'),
        });
    }

    function resolve() {
        router.patch(update.url({ environment, issue: issue.id }), { status: 'resolved' }, { preserveScroll: true });
    }

    return (
        <Card className="gap-0 overflow-hidden bg-surface py-0">
            <CardHeader className="border-b px-5 py-3">
                <CardTitle className="text-sm font-semibold">
                    Activity
                </CardTitle>
            </CardHeader>

            <CardContent className="relative flex flex-col px-5 py-4">
                <div className="relative flex flex-col gap-4 pb-4">
                    {timeline.length > 0 && (
                        <>
                            <div className="absolute top-4 bottom-0 left-3 z-0 w-px border-l border-dashed border-neutral-300 dark:border-neutral-700/50" />
                            {timeline.map((entry) => (
                                <TimelineEntry key={entry.id} entry={entry} />
                            ))}
                        </>
                    )}
                </div>

                <form onSubmit={submit} className="space-y-3">
                    <MarkdownEditor
                        value={data.body}
                        onChange={(value) => setData('body', value)}
                        placeholder="Leave a comment…"
                    />
                    {errors.body && (
                        <p className="text-sm text-destructive">
                            {errors.body}
                        </p>
                    )}
                    <div className="flex items-center justify-end gap-2">
                        {issue.status !== 'resolved' && (
                            <button
                                type="button"
                                onClick={resolve}
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
            </CardContent>
        </Card>
    );
}
