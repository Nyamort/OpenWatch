import { Head, usePage } from '@inertiajs/react';
import { InfoRow, Section } from '@/components/analytics/detail-card';
import { Timeline, type TimelineSpan } from '@/components/analytics/timeline';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatBytes, formatDuration } from '@/lib/utils';
import { index as commandsIndex, type as commandsType } from '@/routes/analytics/commands';
import type { BreadcrumbItem } from '@/types';

interface CommandSummary {
    id: string;
    name: string;
    command: string | null;
    class: string | null;
    exit_code: number | null;
    recorded_at: string;
    duration: number | null;
    peak_memory_usage: number | null;
    server: string;
    queries: number;
    logs: number;
    cache_events: number;
    jobs_queued: number;
    notifications: number;
    outgoing_requests: number;
    exceptions: number;
    mail_count: number;
}

interface ExecutionSpan {
    span_type: string;
    timestamp: string;
    duration: number;
    offset: number;
    name: string;
    description: string;
    [key: string]: unknown;
}

interface ExecutionStage {
    id: string;
    name: string;
    description: string;
    duration: number;
    offset: number;
    spans: ExecutionSpan[];
}

interface Execution {
    id: string;
    name: string;
    description: string;
    status: number;
    duration: number;
    offset: number;
    variant: 'success' | 'warning' | 'error';
    stages: ExecutionStage[];
}

interface Props {
    analytics: {
        summary: CommandSummary;
        rows: {
            executions: Execution[];
        };
    };
}

function executionsToTimelineSpans(executions: Execution[]): TimelineSpan[] {
    return executions.map((execution) => ({
        id: execution.id,
        label: execution.name,
        sublabel: execution.description || undefined,
        durationUs: Math.max(0, execution.duration),
        offsetUs: execution.offset,
        color: 'teal' as const,
        children: execution.stages.map((stage) => ({
            id: `${execution.id}-${stage.id}`,
            label: stage.name,
            sublabel: stage.description || undefined,
            durationUs: Math.max(0, stage.duration),
            offsetUs: stage.offset,
            children:
                stage.spans.length > 0
                    ? stage.spans.map((span, i) => ({
                          id: `${stage.id}-span-${i}`,
                          label: span.name.toUpperCase(),
                          sublabel: span.description || undefined,
                          durationUs:
                              span.duration > 0 ? span.duration : null,
                          offsetUs: span.offset,
                      }))
                    : undefined,
        })),
    }));
}

function exitCodeBadge(exitCode: number | null) {
    if (exitCode === null) {
        return <Badge variant="secondary">—</Badge>;
    }

    return (
        <Badge variant={exitCode === 0 ? 'success' : 'destructive'}>
            {exitCode}
        </Badge>
    );
}

export default function CommandShow({ analytics }: Props) {
    const { summary, rows } = analytics;
    const spans = executionsToTimelineSpans(rows.executions);
    const { props } = usePage();
    const { activeEnvironment } = props as {
        activeEnvironment?: { slug: string } | null;
    };

    const envSlug = activeEnvironment?.slug ?? '';

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Commands',
            href: envSlug
                ? commandsIndex.url({ environment: envSlug })
                : '#',
        },
        {
            title: summary.name,
            href: envSlug
                ? commandsType.url(
                      { environment: envSlug, command: 0 },
                      { query: { name: summary.name } },
                  )
                : '#',
        },
        { title: summary.recorded_at, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head />
            <div className="flex flex-col gap-6 p-6">
                <Card className="gap-0 bg-surface py-0">
                    <CardHeader className="flex flex-row items-center gap-3 border-b py-4">
                        <span className="truncate font-mono text-sm font-medium">
                            {summary.command ?? summary.name}
                        </span>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-8 py-6">
                        <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                            <Section title="Info">
                                <InfoRow
                                    label="Exit Code"
                                    value={exitCodeBadge(summary.exit_code)}
                                />
                                <InfoRow
                                    label="Date"
                                    value={summary.recorded_at}
                                />
                                <InfoRow
                                    label="Duration"
                                    value={formatDuration(summary.duration)}
                                />
                                <InfoRow
                                    label="Peak Memory"
                                    value={formatBytes(
                                        summary.peak_memory_usage,
                                    )}
                                />
                                <InfoRow
                                    label="Server"
                                    value={summary.server}
                                />
                            </Section>

                            <Section title="Events">
                                <InfoRow
                                    label="Queries"
                                    value={summary.queries}
                                />
                                <InfoRow label="Mail" value={summary.mail_count} />
                                <InfoRow
                                    label="Cache"
                                    value={summary.cache_events}
                                />
                                <InfoRow
                                    label="Outgoing Requests"
                                    value={summary.outgoing_requests}
                                />
                                <InfoRow
                                    label="Notifications"
                                    value={summary.notifications}
                                />
                                <InfoRow
                                    label="Queued Jobs"
                                    value={summary.jobs_queued}
                                />
                            </Section>
                        </div>
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
