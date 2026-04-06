import { Head, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn, formatDuration } from '@/lib/utils';
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
    };
}

const statusVariant: Record<string, 'success' | 'warning' | 'destructive'> = {
    processed: 'success',
    released: 'warning',
    failed: 'destructive',
};

function formatBytes(bytes: number | null): string {
    if (bytes === null) return '—';
    if (bytes === 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${units[i]}`;
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

function Section({ title, children, className }: { title?: string; children: ReactNode; className?: string }) {
    return (
        <div className={cn('flex flex-col gap-1', className)}>
            {title && (
                <h3 className="mb-1 text-base font-semibold text-foreground">{title}</h3>
            )}
            {children}
        </div>
    );
}

export default function JobShow({ analytics }: Props) {
    const { summary } = analytics;
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
                ? jobsType.url({ environment: envSlug, job: 0 }, { query: { name: summary.name } })
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
                            {summary.name}
                        </span>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-8 py-6">
                        <Section>
                            <InfoRow
                                label="Status"
                                value={
                                    <Badge variant={statusVariant[summary.status] ?? 'secondary'}>
                                        {summary.status}
                                    </Badge>
                                }
                            />
                            <InfoRow label="Date" value={summary.recorded_at} />
                            <InfoRow label="Duration" value={formatDuration(summary.duration)} />
                            <InfoRow label="Connection" value={summary.connection} />
                            <InfoRow label="Queue" value={summary.queue} />
                            <InfoRow label="Peak Memory" value={formatBytes(summary.peak_memory_usage)} />
                            <InfoRow label="Server" value={summary.server} />
                        </Section>

                        {summary.user !== null && (
                            <Section title="User">
                                <InfoRow label="Name" value={summary.user_name} />
                                <InfoRow label="Email" value={summary.user_email} />
                            </Section>
                        )}

                        <Section title="Events">
                            <div className="grid grid-cols-2 gap-x-8">
                                <div>
                                    <InfoRow label="Queries" value={summary.queries} />
                                    <InfoRow label="Cache" value={summary.cache_events} />
                                    <InfoRow label="Notifications" value={summary.notifications} />
                                </div>
                                <div>
                                    <InfoRow label="Outgoing Requests" value={summary.outgoing_requests} />
                                    <InfoRow label="Queued Jobs" value={summary.jobs_queued} />
                                    <InfoRow label="Exceptions" value={summary.exceptions} />
                                </div>
                            </div>
                        </Section>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
