import { Head, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Timeline, type TimelineSpan } from '@/components/analytics/timeline';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDuration } from '@/lib/utils';
import { index as requestsIndex, route as requestsRoute } from '@/routes/analytics/requests';
import type { BreadcrumbItem } from '@/types';

interface RequestSummary {
    id: string;
    recorded_at: string;
    method: string;
    url: string;
    route_path: string | null;
    route_name: string | null;
    status_code: number;
    duration: number | null;
    server: string;
    user: string | null;
    user_name: string | null;
    user_email: string | null;
    ip: string | null;
    request_size: number | null;
    response_size: number | null;
    peak_memory_usage: number | null;
    queries: number;
    mail_count: number;
    cache_events: number;
    outgoing_requests: number;
    notifications: number;
    jobs_queued: number;
    exceptions: number;
    logs: number;
}

interface ExecutionSpan {
    span_type: string;
    timestamp: string;
    duration: number; // microseconds
    offset: number; // microseconds from request start
    name: string;
    description: string;
    [key: string]: unknown;
}

interface ExecutionStage {
    id: string;
    name: string;
    description: string;
    duration: number; // microseconds
    offset: number; // microseconds from request start
    spans: ExecutionSpan[];
}

interface Execution {
    id: string;
    name: string;
    description: string;
    status: number;
    duration: number; // microseconds
    offset: number; // microseconds
    variant: 'success' | 'warning' | 'error';
    stages: ExecutionStage[];
}

interface Props {
    analytics: {
        summary: RequestSummary;
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
            children: stage.spans.length > 0
                ? stage.spans.map((span, i) => ({
                    id: `${stage.id}-span-${i}`,
                    label: span.name.toUpperCase(),
                    sublabel: span.description || undefined,
                    durationUs: span.duration > 0 ? span.duration : null,
                    offsetUs: span.offset,
                }))
                : undefined,
        })),
    }));
}

function formatBytes(bytes: number | null): string {
    if (bytes === null) return '—';
    if (bytes === 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${units[i]}`;
}

function StatusCodeBadge({ code }: { code: number }) {
    const className =
        code < 400
            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400'
            : code < 500
              ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
              : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400';

    return (
        <span className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold ${className}`}>
            {code}
        </span>
    );
}

function InfoRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-baseline gap-2 py-1 text-sm first:pt-0 last:pb-0">
            <span className="shrink-0 uppercase text-muted-foreground">{label}</span>
            <span className="relative -bottom-px min-w-6 grow border-b-2 border-dotted border-neutral-300 dark:border-white/20" />
            <span className="shrink-0 text-right font-medium">{value ?? '—'}</span>
        </div>
    );
}

function Section({ title, children }: { title?: string; children: ReactNode }) {
    return (
        <div className="flex flex-col gap-1">
            {title && (
                <h3 className="mb-1 text-base font-semibold text-foreground">
                    {title}
                </h3>
            )}
            {children}
        </div>
    );
}

export default function RequestShow({ analytics }: Props) {
    const { summary, rows } = analytics;
    const spans = executionsToTimelineSpans(rows.executions);
    const { props } = usePage();
    const { activeEnvironment } = props as {
        activeEnvironment?: { slug: string } | null;
    };

    const envSlug = activeEnvironment?.slug ?? '';

    const routeHref =
        envSlug && summary.route_path
            ? requestsRoute.url(
                  { environment: envSlug },
                  { query: { route_path: summary.route_path } },
              )
            : envSlug
              ? requestsIndex.url({ environment: envSlug })
              : '#';

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Requests',
            href: envSlug ? requestsIndex.url({ environment: envSlug }) : '#',
        },
        {
            title: summary.route_path ?? summary.url,
            href: routeHref,
        },
        { title: summary.url, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head />
            <div className="flex flex-col gap-6 p-6">
                <Card className="gap-0 bg-surface py-0">
                    <CardHeader className="flex flex-row items-center gap-3 border-b py-4">
                        <span className="font-mono text-sm font-bold text-foreground">
                            {summary.method}
                        </span>
                        <span className="truncate font-mono text-sm font-medium">
                            {summary.url}
                        </span>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-8 py-6">
                        {/* Date & Status */}
                        <Section>
                            <InfoRow label="Date" value={summary.recorded_at} />
                            <InfoRow
                                label="Status Code"
                                value={<StatusCodeBadge code={summary.status_code} />}
                            />
                        </Section>

                        {/* Performance */}
                        <Section>
                            <InfoRow label="Server" value={summary.server || '—'} />
                            <InfoRow
                                label="Duration"
                                value={formatDuration(summary.duration)}
                            />
                            <InfoRow
                                label="Request Size"
                                value={formatBytes(summary.request_size)}
                            />
                            <InfoRow
                                label="Response Size"
                                value={formatBytes(summary.response_size)}
                            />
                            <InfoRow
                                label="Peak Memory"
                                value={formatBytes(summary.peak_memory_usage)}
                            />
                        </Section>

                        {/* User */}
                        <Section title="User">
                            <InfoRow label="IP Address" value={summary.ip} />
                            {summary.user !== null && (
                                <>
                                    <InfoRow label="Name" value={summary.user_name} />
                                    <InfoRow label="Email" value={summary.user_email} />
                                </>
                            )}
                        </Section>

                        {/* Events */}
                        <Section title="Events">
                            <div className="grid grid-cols-2 gap-x-8">
                                <div>
                                    <InfoRow label="Queries" value={summary.queries} />
                                    <InfoRow label="Mail" value={summary.mail_count} />
                                    <InfoRow label="Cache" value={summary.cache_events} />
                                </div>
                                <div>
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
