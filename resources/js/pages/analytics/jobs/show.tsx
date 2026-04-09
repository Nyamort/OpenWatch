import { Head, usePage } from '@inertiajs/react';
import { InfoRow, Section } from '@/components/analytics/detail-card';
import {
    Timeline,
    executionsToTimelineSpans,
    type Execution,
} from '@/components/analytics/timeline';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatBytes, formatDuration } from '@/lib/utils';
import { index as jobsIndex, type as jobsType } from '@/routes/analytics/jobs';
import type { BreadcrumbItem } from '@/types';

interface AttemptSummary {
    attempt_id: string;
    attempt: number;
    name: string;
    status: string;
    recorded_at: string;
    duration: number | null;
    connection: string;
    queue: string;
    peak_memory_usage: number | null;
    server: string;
    user: string | null;
    user_name: string | null;
    user_email: string | null;
    queries: number;
    logs: number;
    cache_events: number;
    outgoing_requests: number;
    notifications: number;
    jobs_queued: number;
    exceptions: number;
}

interface Props {
    analytics: {
        summary: AttemptSummary;
        rows: {
            executions: Execution[];
        };
    };
}

const statusVariant: Record<string, 'success' | 'warning' | 'destructive'> = {
    processed: 'success',
    released: 'warning',
    failed: 'destructive',
};

export default function JobShow({ analytics }: Props) {
    const { summary, rows } = analytics;
    const spans = executionsToTimelineSpans(rows.executions);
    const { props } = usePage();
    const { activeEnvironment } = props as {
        activeEnvironment?: { slug: string } | null;
    };

    const envSlug = activeEnvironment?.slug ?? '';

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Jobs',
            href: envSlug ? jobsIndex.url({ environment: envSlug }) : '#',
        },
        {
            title: summary.name,
            href: envSlug
                ? jobsType.url(
                      { environment: envSlug, job: 0 },
                      { query: { name: summary.name } },
                  )
                : '#',
        },
        { title: summary.name, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head />
            <div className="flex flex-col gap-6 p-6">
                <Card className="gap-0 bg-surface py-0">
                    <CardHeader className="flex flex-row items-center gap-3 border-b py-4">
                        <span className="truncate font-mono text-sm font-medium">
                            {summary.name}
                        </span>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-8 py-6">
                        <Section>
                            <InfoRow
                                label="Status"
                                value={
                                    <Badge
                                        variant={
                                            statusVariant[summary.status] ??
                                            'secondary'
                                        }
                                    >
                                        {summary.status}
                                    </Badge>
                                }
                            />
                            <InfoRow label="Date" value={summary.recorded_at} />
                            <InfoRow
                                label="Duration"
                                value={formatDuration(summary.duration)}
                            />
                            <InfoRow
                                label="Connection"
                                value={summary.connection}
                            />
                            <InfoRow label="Queue" value={summary.queue} />
                            <InfoRow
                                label="Peak Memory"
                                value={formatBytes(summary.peak_memory_usage)}
                            />
                            <InfoRow label="Server" value={summary.server} />
                        </Section>

                        {summary.user !== null && (
                            <Section title="User">
                                <InfoRow
                                    label="Name"
                                    value={summary.user_name}
                                />
                                <InfoRow
                                    label="Email"
                                    value={summary.user_email}
                                />
                            </Section>
                        )}

                        <Section title="Events">
                            <div className="grid grid-cols-2 gap-x-8">
                                <div>
                                    <InfoRow
                                        label="Queries"
                                        value={summary.queries}
                                    />
                                    <InfoRow
                                        label="Cache"
                                        value={summary.cache_events}
                                    />
                                    <InfoRow
                                        label="Notifications"
                                        value={summary.notifications}
                                    />
                                </div>
                                <div>
                                    <InfoRow
                                        label="Outgoing Requests"
                                        value={summary.outgoing_requests}
                                    />
                                    <InfoRow
                                        label="Queued Jobs"
                                        value={summary.jobs_queued}
                                    />
                                    <InfoRow
                                        label="Exceptions"
                                        value={summary.exceptions}
                                    />
                                </div>
                            </div>
                        </Section>
                    </CardContent>
                </Card>
                {(summary.duration ?? 0) > 0 && (
                    <Timeline
                        totalDurationUs={summary.duration!}
                        spans={spans}
                    />
                )}
            </div>
        </AppLayout>
    );
}
