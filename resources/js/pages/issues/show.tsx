import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { ActivityFeed, type TimelineEntry } from '@/components/issues/activity-feed';
import { IssueDetailSidebar } from '@/components/issues/issue-detail-sidebar';
import ExceptionCard from '@/components/exceptions/exception-card';
import type { ExceptionOccurrence } from '@/components/exceptions/types';
import { MarkdownEditor } from '@/components/issues/markdown-editor';
import AppLayout from '@/layouts/app-layout';
import { index, show } from '@/routes/issues';
import type { BreadcrumbItem } from '@/types';

interface Member {
    id: number;
    name: string;
    email: string;
}

interface Issue {
    id: number;
    title: string;
    subtitle: string | null;
    type: 'exception' | 'performance' | 'other';
    status: string;
    priority: string;
    assignee: Member | null;
    first_seen_at: string;
    last_seen_at: string;
    occurrence_count: number;
}

interface Environment {
    id: number;
    slug: string;
    name: string;
}

interface ExceptionSummary {
    group_key: string;
    class: string;
    message: string;
    file: string;
    line: number;
    handled: boolean | number;
    code: string | null;
    php_version: string | null;
    laravel_version: string | null;
    trace: string;
    recorded_at: string;
    [key: string]: unknown;
}

interface Props {
    environment: Environment;
    issue: Issue;
    timeline: TimelineEntry[];
    exceptionSummary?: ExceptionSummary | null;
    members: Member[];
}

function summaryToOccurrence(summary: ExceptionSummary): ExceptionOccurrence {
    let trace: ExceptionOccurrence['trace'] = [];
    try {
        trace = JSON.parse(summary.trace);
    } catch {
        // malformed trace — leave empty
    }

    return {
        group: summary.group_key,
        timestamp: summary.recorded_at,
        file: summary.file,
        line: summary.line,
        class: summary.class,
        message: summary.message,
        handled: Boolean(summary.handled),
        code: summary.code ?? '0',
        php_version: summary.php_version ?? '',
        laravel_version: summary.laravel_version ?? '',
        trace,
    };
}

export default function IssueShow({ environment, issue, timeline, exceptionSummary, members }: Props) {
    const baseUrl = index.url(environment);
    const issueUrl = show.url({ environment, issue: issue.id });
    const [body, setBody] = useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Issues', href: baseUrl },
        { title: `#${issue.id}`, href: issueUrl },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={issue.title} />
            <div className="flex items-start gap-6 p-6">
                <div className="min-w-0 flex-1 space-y-6">
                    <h1 className="text-3xl font-semibold break-all">
                        {issue.subtitle
                            ? `${issue.title}: ${issue.subtitle}`
                            : issue.title}
                    </h1>
                    <MarkdownEditor value={body} onChange={setBody} />
                    {issue.type === 'exception' && exceptionSummary && (
                        <ExceptionCard exception={summaryToOccurrence(exceptionSummary)} />
                    )}
                    <ActivityFeed timeline={timeline} environment={environment} issue={issue} />
                </div>
                <div className="w-64 shrink-0 sticky top-20">
                    <IssueDetailSidebar
                        environmentSlug={environment.slug}
                        issue={issue}
                        members={members}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
