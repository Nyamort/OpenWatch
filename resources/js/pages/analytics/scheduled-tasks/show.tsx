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
import {
    index as scheduledTasksIndex,
    type as scheduledTasksType,
} from '@/routes/analytics/scheduled-tasks';
import type { BreadcrumbItem } from '@/types';

interface TaskSummary {
    id: string;
    name: string;
    cron: string;
    status: string;
    recorded_at: string;
    duration: number | null;
    peak_memory_usage: number | null;
    server: string;
    without_overlapping: number;
    on_one_server: number;
    run_in_background: number;
    even_in_maintenance_mode: number;
    queries: number;
    logs: number;
    cache_events: number;
    jobs_queued: number;
    notifications: number;
    outgoing_requests: number;
    mail_count: number;
}

interface Props {
    analytics: {
        summary: TaskSummary;
        rows: {
            executions: Execution[];
        };
    };
}

function statusBadge(status: string) {
    const variant =
        status === 'processed'
            ? 'success'
            : status === 'skipped'
              ? 'warning'
              : 'destructive';

    return (
        <Badge variant={variant} className="capitalize">
            {status}
        </Badge>
    );
}

function boolValue(value: number) {
    return value ? 'Yes' : 'No';
}

export default function ScheduledTaskShow({ analytics }: Props) {
    const { summary, rows } = analytics;
    const spans = executionsToTimelineSpans(rows.executions);
    const { props } = usePage();
    const { activeEnvironment } = props as {
        activeEnvironment?: { slug: string } | null;
    };

    const envSlug = activeEnvironment?.slug ?? '';

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Scheduled Tasks',
            href: envSlug
                ? scheduledTasksIndex.url({ environment: envSlug })
                : '#',
        },
        {
            title: summary.name,
            href: envSlug
                ? scheduledTasksType.url(
                      { environment: envSlug, scheduledTask: 0 },
                      { query: { name: summary.name, cron: summary.cron } },
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
                            <InfoRow label="Date" value={summary.recorded_at} />
                            <InfoRow
                                label="Status"
                                value={statusBadge(summary.status)}
                            />
                            <InfoRow
                                label="Duration"
                                value={formatDuration(summary.duration)}
                            />
                            <InfoRow
                                label="Peak Memory"
                                value={formatBytes(summary.peak_memory_usage)}
                            />
                            <InfoRow label="Server" value={summary.server} />
                            <InfoRow
                                label="Without Overlapping"
                                value={boolValue(summary.without_overlapping)}
                            />
                            <InfoRow
                                label="On One Server"
                                value={boolValue(summary.on_one_server)}
                            />
                            <InfoRow
                                label="Run in Background"
                                value={boolValue(summary.run_in_background)}
                            />
                            <InfoRow
                                label="Even in Maintenance Mode"
                                value={boolValue(
                                    summary.even_in_maintenance_mode,
                                )}
                            />
                        </Section>

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
                                        label="Mail"
                                        value={summary.mail_count}
                                    />
                                    <InfoRow
                                        label="Outgoing Requests"
                                        value={summary.outgoing_requests}
                                    />
                                    <InfoRow
                                        label="Queued Jobs"
                                        value={summary.jobs_queued}
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
