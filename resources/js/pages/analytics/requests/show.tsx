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
    bootstrap: number | null;
    before_middleware: number | null;
    action: number | null;
    render: number | null;
    after_middleware: number | null;
    sending: number | null;
    terminating: number | null;
    server: string;
    user: string | null;
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

interface QueryRow {
    id: number;
    recorded_at: string;
    duration: number;
    sql_normalized: string;
    connection: string;
}

interface ExceptionRow {
    id: number;
    recorded_at: string;
    class: string;
    message: string;
    handled: boolean;
}

interface LogRow {
    id: number;
    recorded_at: string;
    level: string;
    message: string;
}

interface Props {
    analytics: {
        summary: RequestSummary;
        rows: {
            queries: QueryRow[];
            exceptions: ExceptionRow[];
            logs: LogRow[];
        };
    };
}

function buildTimelineSpans(
    summary: RequestSummary,
    queries: QueryRow[],
    exceptions: ExceptionRow[],
    logs: LogRow[],
): TimelineSpan[] {
    const totalMs = (summary.duration ?? 0) / 1000;
    const requestEndMs = new Date(summary.recorded_at).getTime();
    const requestStartMs = requestEndMs - totalMs;

    const toOffset = (recordedAt: string): number =>
        Math.max(0, Math.min(totalMs, new Date(recordedAt).getTime() - requestStartMs));

    // Sequential lifecycle phases
    const phases: { id: string; label: string; us: number | null }[] = [
        { id: 'bootstrap', label: 'Bootstrap', us: summary.bootstrap },
        { id: 'before_middleware', label: 'Before MW', us: summary.before_middleware },
        { id: 'action', label: 'Controller', us: summary.action },
        { id: 'render', label: 'Render', us: summary.render },
        { id: 'after_middleware', label: 'After MW', us: summary.after_middleware },
        { id: 'sending', label: 'Sending', us: summary.sending },
        { id: 'terminating', label: 'Terminating', us: summary.terminating },
    ];

    let cursorMs = 0;
    const phaseSpans: TimelineSpan[] = phases
        .filter((p) => p.us != null && p.us > 0)
        .map((p): TimelineSpan => {
            const durationMs = p.us! / 1000;
            const span: TimelineSpan = {
                id: p.id,
                label: p.label,
                durationMs,
                offsetMs: cursorMs,
            };
            cursorMs += durationMs;
            return span;
        });

    // Events positioned by their actual timestamp offset
    const eventSpans: TimelineSpan[] = [
        ...queries.map((q, i): TimelineSpan => {
            const durationMs = q.duration / 1000;
            const offsetMs = Math.max(0, toOffset(q.recorded_at) - durationMs);
            return {
                id: `query-${i}`,
                label: 'DB',
                sublabel: q.sql_normalized.replace(/\s+/g, ' ').slice(0, 80),
                durationMs,
                offsetMs,
            };
        }),
        ...exceptions.map((e, i): TimelineSpan => ({
            id: `exception-${i}`,
            label: 'Exception',
            sublabel: e.class.split('\\').pop(),
            durationMs: null,
            offsetMs: toOffset(e.recorded_at),
        })),
        ...logs.map((l, i): TimelineSpan => ({
            id: `log-${i}`,
            label: l.level.toUpperCase(),
            sublabel: l.message.slice(0, 60),
            durationMs: null,
            offsetMs: toOffset(l.recorded_at),
        })),
    ].sort((a, b) => a.offsetMs - b.offsetMs);

    const children = [...phaseSpans, ...eventSpans];

    return [
        {
            id: 'request',
            label: 'Request',
            sublabel: summary.route_path ?? summary.url,
            durationMs: totalMs,
            offsetMs: 0,
            color: 'teal',
            children: children.length > 0 ? children : undefined,
        },
    ];
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
    const spans = buildTimelineSpans(summary, rows.queries, rows.exceptions, rows.logs);
    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as {
        activeOrganization?: { slug: string } | null;
        activeProject?: { slug: string } | null;
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
                        totalDurationMs={summary.duration! / 1000}
                        spans={spans}
                    />
                )}
            </div>
        </AppLayout>
    );
}
