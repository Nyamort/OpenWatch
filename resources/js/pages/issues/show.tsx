import { Head, usePage } from '@inertiajs/react';
import { IssueTimeline } from '@/components/issues/issue-timeline';
import AppLayout from '@/layouts/app-layout';
import { index, show } from '@/routes/issues';
import type { BreadcrumbItem } from '@/types';
import type { TimelineEntry, TimelineUser } from '@/types/timeline';

interface Issue {
    id: number;
    title: string;
    subtitle: string | null;
    status: 'open' | 'resolved' | 'ignored';
}

interface Environment {
    id: number;
    slug: string;
    name: string;
}

interface Props {
    environment: Environment;
    issue: Issue;
    timeline: { data: TimelineEntry[] };
    viewerRole: 'owner' | 'admin' | 'developer' | 'viewer' | null;
}

export default function IssueShow({
    environment,
    issue,
    timeline,
    viewerRole,
}: Props) {
    const baseUrl = index.url(environment);
    const issueUrl = show.url({ environment, issue: issue.id });
    const { props } = usePage<{ auth: { user: TimelineUser | null } }>();
    const currentUser = props.auth.user;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Issues', href: baseUrl },
        { title: `#${issue.id}`, href: issueUrl },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={issue.title} />
            <div className="mx-auto max-w-4xl space-y-6 p-6">
                <header className="space-y-1">
                    <h1 className="text-3xl font-semibold break-all">
                        {issue.subtitle
                            ? `${issue.title}: ${issue.subtitle}`
                            : issue.title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        #{issue.id} · {issue.status}
                    </p>
                </header>
                <IssueTimeline
                    entries={timeline.data}
                    environmentSlug={environment.slug}
                    issueId={issue.id}
                    issueStatus={issue.status}
                    currentUser={currentUser}
                    viewerRole={viewerRole}
                />
            </div>
        </AppLayout>
    );
}
