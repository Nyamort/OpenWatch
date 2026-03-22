import { CommentComposer } from '@/components/issues/comment-composer';
import { SnapshotRenderer } from '@/components/issues/snapshot-renderer';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';

interface IssueSource {
    id: number;
    source_type: string;
    trace_id: string | null;
    group_key: string | null;
    execution_id: string | null;
    snapshot: Record<string, unknown> | null;
    created_at: string;
}

interface IssueActivity {
    id: number;
    type: string;
    metadata: Record<string, unknown> | null;
    created_at: string;
    actor: { id: number; name: string } | null;
}

interface Comment {
    id: number;
    body: string;
    edited_at: string | null;
    created_at: string;
    updated_at: string;
    author: { id: number; name: string };
}

interface Issue {
    id: number;
    title: string;
    type: string;
    status: string;
    priority: string;
    occurrence_count: number;
    first_seen_at: string;
    last_seen_at: string;
    assignee: { id: number; name: string; email: string } | null;
    sources: IssueSource[];
    activities: IssueActivity[];
}

interface Organization {
    id: number;
    slug: string;
    name: string;
}

interface Project {
    id: number;
    slug: string;
    name: string;
}

interface Environment {
    id: number;
    slug: string;
    name: string;
}

interface Props {
    organization: Organization;
    project: Project;
    environment: Environment;
    issue: Issue;
    comments: Comment[];
}

const statusVariantMap: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    open: 'destructive',
    resolved: 'secondary',
    ignored: 'outline',
};

const priorityVariantMap: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    critical: 'destructive',
    high: 'default',
    medium: 'secondary',
    low: 'outline',
};

function activityLabel(activity: IssueActivity): string {
    switch (activity.type) {
        case 'created':
            return 'created this issue';
        case 'status_changed':
            return `changed status from ${activity.metadata?.from} to ${activity.metadata?.to}`;
        case 'assigned':
            return `changed assignee`;
        case 'commented':
            return 'added a comment';
        default:
            return activity.type;
    }
}

export default function IssueShow({ organization, project, environment, issue, comments }: Props) {
    const baseUrl = `/organizations/${organization.slug}/projects/${project.slug}/environments/${environment.slug}/issues`;
    const issueUrl = `${baseUrl}/${issue.id}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: organization.name, href: `/organizations/${organization.slug}` },
        { title: project.name, href: `/organizations/${organization.slug}/projects/${project.slug}` },
        { title: 'Issues', href: baseUrl },
        { title: `#${issue.id}`, href: issueUrl },
    ];

    const statusForm = useForm({ status: issue.status });

    function changeStatus(newStatus: string) {
        router.patch(
            `${issueUrl}`,
            { status: newStatus },
            { preserveScroll: true },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={issue.title} />
            <div className="flex gap-6 p-6">
                {/* Left column */}
                <div className="flex-1 min-w-0 space-y-6">
                    <div>
                        <h1 className="text-xl font-semibold break-all">{issue.title}</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            First seen {new Date(issue.first_seen_at).toLocaleString()} &middot; Last seen{' '}
                            {new Date(issue.last_seen_at).toLocaleString()} &middot;{' '}
                            {issue.occurrence_count.toLocaleString()} occurrence{issue.occurrence_count !== 1 ? 's' : ''}
                        </p>
                    </div>

                    {/* Snapshot */}
                    <SnapshotRenderer
                        sources={issue.sources}
                        issueType={issue.type}
                        issueTitle={issue.title}
                    />

                    {/* Activity timeline */}
                    {issue.activities.length > 0 && (
                        <section>
                            <h2 className="mb-3 text-sm font-medium">Recent Activity</h2>
                            <ul className="space-y-2">
                                {issue.activities.map((activity) => (
                                    <li key={activity.id} className="flex items-start gap-2 text-sm">
                                        <span className="mt-0.5 size-2 rounded-full bg-muted-foreground/40 shrink-0 mt-1.5" />
                                        <span>
                                            <span className="font-medium">{activity.actor?.name ?? 'System'}</span>{' '}
                                            {activityLabel(activity)}{' '}
                                            <span className="text-muted-foreground">
                                                &middot; {new Date(activity.created_at).toLocaleString()}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {/* Comments */}
                    <section>
                        <h2 className="mb-3 text-sm font-medium">Comments</h2>
                        {comments.length === 0 && (
                            <p className="text-sm text-muted-foreground">No comments yet.</p>
                        )}
                        <ul className="space-y-4 mb-4">
                            {comments.map((comment) => (
                                <li key={comment.id} className="rounded-lg border bg-card p-4">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-sm font-medium">{comment.author.name}</span>
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(comment.created_at).toLocaleString()}
                                            {comment.edited_at && ' (edited)'}
                                        </span>
                                    </div>
                                    <p className="text-sm whitespace-pre-wrap">{comment.body}</p>
                                </li>
                            ))}
                        </ul>
                        <CommentComposer submitUrl={`${issueUrl}/comments`} />
                    </section>
                </div>

                {/* Right sidebar */}
                <div className="w-64 shrink-0 space-y-6">
                    <div className="rounded-lg border bg-card p-4 space-y-4">
                        <div>
                            <p className="mb-1 text-xs font-medium text-muted-foreground uppercase tracking-wide">Status</p>
                            <Badge variant={statusVariantMap[issue.status] ?? 'outline'}>{issue.status}</Badge>
                        </div>
                        <div>
                            <p className="mb-1 text-xs font-medium text-muted-foreground uppercase tracking-wide">Priority</p>
                            <Badge variant={priorityVariantMap[issue.priority] ?? 'outline'}>{issue.priority}</Badge>
                        </div>
                        <div>
                            <p className="mb-1 text-xs font-medium text-muted-foreground uppercase tracking-wide">Assignee</p>
                            <p className="text-sm">{issue.assignee?.name ?? 'Unassigned'}</p>
                        </div>
                    </div>

                    {/* Quick actions */}
                    <div className="space-y-2">
                        {issue.status === 'open' && (
                            <>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="w-full"
                                    onClick={() => changeStatus('resolved')}
                                >
                                    Mark Resolved
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="w-full"
                                    onClick={() => changeStatus('ignored')}
                                >
                                    Ignore
                                </Button>
                            </>
                        )}
                        {issue.status !== 'open' && (
                            <Button
                                size="sm"
                                variant="outline"
                                className="w-full"
                                onClick={() => changeStatus('open')}
                            >
                                Reopen
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
