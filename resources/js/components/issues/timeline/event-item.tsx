import { formatDistanceToNow, parseISO } from 'date-fns';
import type { ReactNode } from 'react';
import { UserAvatar } from '@/components/issues/timeline/user-avatar';
import type {
    AssignmentChangedEntry,
    IssueCreatedEntry,
    StatusChangedEntry,
    TimelineEntry,
    TimelineUser,
} from '@/types/timeline';

interface Props {
    entry: TimelineEntry & (IssueCreatedEntry | StatusChangedEntry | AssignmentChangedEntry);
}

export function TimelineEventItem({ entry }: Props) {
    const occurredAt = parseISO(entry.occurred_at);
    const timeAgo = formatDistanceToNow(occurredAt, { addSuffix: true });

    return (
        <li className="relative flex items-center gap-3 py-1.5 pl-10">
            <span className="absolute left-2.5 top-1/2 size-3 -translate-y-1/2 rounded-full border-2 border-background bg-primary" />
            <UserAvatar user={entry.actor} size="sm" />
            <div className="flex min-w-0 items-center gap-1 text-sm text-muted-foreground">
                <span className="font-medium text-foreground">
                    {entry.actor?.name ?? 'System'}
                </span>
                <span>{describe(entry)}</span>
                <span className="text-muted-foreground/60">·</span>
                <time
                    dateTime={entry.occurred_at}
                    title={occurredAt.toLocaleString()}
                    className="shrink-0"
                >
                    {timeAgo}
                </time>
            </div>
        </li>
    );
}

function describe(
    entry: IssueCreatedEntry | StatusChangedEntry | AssignmentChangedEntry,
): ReactNode {
    switch (entry.kind) {
        case 'issue_created':
            return <>created the issue</>;
        case 'status_changed':
            return (
                <>
                    updated the status to{' '}
                    <StatusPill status={entry.data.to} />
                </>
            );
        case 'assignment_changed':
            return <AssignmentLabel from={entry.data.from_user} to={entry.data.to_user} />;
    }
}

function StatusPill({ status }: { status: string }) {
    return (
        <span className="rounded-sm bg-muted px-1.5 py-0.5 text-xs font-medium capitalize text-foreground">
            {status}
        </span>
    );
}

function AssignmentLabel({
    from,
    to,
}: {
    from: TimelineUser | null;
    to: TimelineUser | null;
}) {
    if (to === null) {
        return <>unassigned {from ? from.name : 'the issue'}</>;
    }

    if (from === null) {
        return (
            <>
                assigned the issue to{' '}
                <span className="font-medium text-foreground">{to.name}</span>
            </>
        );
    }

    return (
        <>
            reassigned the issue to{' '}
            <span className="font-medium text-foreground">{to.name}</span>
        </>
    );
}
