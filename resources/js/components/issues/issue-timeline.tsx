import { TimelineCommentItem } from '@/components/issues/timeline/comment-item';
import { TimelineComposer } from '@/components/issues/timeline/composer';
import { TimelineEventItem } from '@/components/issues/timeline/event-item';
import type { TimelineEntry, TimelineUser } from '@/types/timeline';

interface Props {
    entries: TimelineEntry[];
    environmentSlug: string;
    issueId: number;
    issueStatus: 'open' | 'resolved' | 'ignored';
    currentUser: TimelineUser | null;
    viewerRole: 'owner' | 'admin' | 'developer' | 'viewer' | null;
}

export function IssueTimeline({
    entries,
    environmentSlug,
    issueId,
    issueStatus,
    currentUser,
    viewerRole,
}: Props) {
    const canComment = viewerRole !== null && viewerRole !== 'viewer';
    const canModerate = viewerRole === 'owner' || viewerRole === 'admin';
    const canChangeStatus = canComment;

    return (
        <section className="rounded-xl border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">Activity</h2>
            <ol className="relative space-y-4">
                <span
                    aria-hidden
                    className="absolute left-4 top-2 bottom-2 w-px bg-border"
                />
                {entries.map((entry) =>
                    entry.kind === 'comment' ? (
                        <TimelineCommentItem
                            key={`${entry.kind}-${entry.id}`}
                            entry={entry}
                            environmentSlug={environmentSlug}
                            issueId={issueId}
                            canModerate={canModerate}
                        />
                    ) : (
                        <TimelineEventItem
                            key={`${entry.kind}-${entry.id}`}
                            entry={entry}
                        />
                    ),
                )}
            </ol>
            <div className="mt-6 border-t pt-6">
                <TimelineComposer
                    environmentSlug={environmentSlug}
                    issueId={issueId}
                    currentUser={currentUser}
                    issueStatus={issueStatus}
                    canComment={canComment}
                    canChangeStatus={canChangeStatus}
                />
            </div>
        </section>
    );
}
