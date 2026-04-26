export interface Actor {
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

export interface CommentedEntry extends BaseEntry {
    kind: 'commented';
    comment_id: number;
    body: string;
    edited_at: string | null;
}

export interface StatusWithCommentEntry extends BaseEntry {
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

export const COMMENT_KINDS = new Set([
    'commented',
    'status_updated_with_comment',
    'status_update_comment_updated',
    'status_update_comment_deleted',
]);

export const STATUS_LABELS: Record<string, string> = { open: 'Open', resolved: 'Resolved', ignored: 'Ignored' };
export const STATUS_VERBS: Record<string, string> = { open: 'reopened', resolved: 'resolved', ignored: 'ignored' };
export const PRIORITY_LABELS: Record<string, string> = { none: 'None', low: 'Low', medium: 'Medium', high: 'High' };

export function isCommentEntry(entry: TimelineEntry): entry is CommentedEntry | StatusWithCommentEntry {
    return COMMENT_KINDS.has(entry.kind);
}

export function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

export function relativeTime(dateStr: string): string {
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

export function actionText(entry: TimelineEntry): string {
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
