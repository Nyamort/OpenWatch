import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { index as issuesIndex } from '@/routes/issues';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

interface Metrics {
    requests: {
        total: number;
        error_count: number;
        error_rate: number;
        p95_duration: number;
    };
    exceptions: { total: number; unhandled: number };
    jobs: { total: number; failed: number; failure_rate: number };
    users: { authenticated: number };
}

interface AlertItem {
    id: number;
    name: string;
    metric: string;
    condition: string;
}

interface AlertsSummary {
    count: number;
    alerts: AlertItem[];
}

interface IssueItem {
    id: number;
    title: string;
    type: string;
    priority: string;
    occurrence_count: number;
    last_seen_at: string;
}

interface IssuesSummary {
    count: number;
    issues: IssueItem[];
}

interface Props {
    hasContext: boolean;
    period: string;
    context?: { org: string; project: string; env: string };
    metrics?: Metrics | null;
    alerts?: AlertsSummary | null;
    recentIssues?: IssuesSummary | null;
}

function MetricCard({
    title,
    value,
    subtitle,
    color = 'blue',
}: {
    title: string;
    value: string | number;
    subtitle?: string;
    color?: string;
}) {
    return (
        <div className="rounded-lg border bg-card p-6">
            <p className="text-sm font-medium text-muted-foreground">{title}</p>
            <p
                className={`mt-1 text-3xl font-bold text-${color}-600 dark:text-${color}-400`}
            >
                {value}
            </p>
            {subtitle && (
                <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p>
            )}
        </div>
    );
}

function SectionSkeleton() {
    return <div className="h-24 animate-pulse rounded-lg bg-muted" />;
}

export default function Dashboard({
    hasContext,
    period,
    context,
    metrics,
    alerts,
    recentIssues,
}: Props) {
    const periods = [
        { value: '1h', label: '1h' },
        { value: '24h', label: '24h' },
        { value: '7d', label: '7d' },
        { value: '14d', label: '14d' },
        { value: '30d', label: '30d' },
    ];

    const changePeriod = (p: string) => {
        router.visit(window.location.pathname, {
            data: { period: p },
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">
                            Dashboard
                        </h1>
                        {context && (
                            <p className="mt-1 text-sm text-muted-foreground">
                                {context.org} / {context.project} /{' '}
                                {context.env}
                            </p>
                        )}
                    </div>
                    {/* Period selector */}
                    <div className="flex gap-1 rounded-lg bg-muted p-1">
                        {periods.map((p) => (
                            <button
                                key={p.value}
                                onClick={() => changePeriod(p.value)}
                                className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                    period === p.value
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                {p.label}
                            </button>
                        ))}
                    </div>
                </div>

                {!hasContext ? (
                    <div className="py-20 text-center text-muted-foreground">
                        <p className="text-lg">No project configured yet.</p>
                        <p className="mt-2 text-sm">
                            Create an organization and project to start
                            monitoring.
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Active Alerts Banner */}
                        {alerts === null || alerts === undefined ? (
                            <SectionSkeleton />
                        ) : alerts.count > 0 ? (
                            <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                                <div className="flex items-center gap-2">
                                    <span className="font-semibold text-red-600 dark:text-red-400">
                                        ⚠ {alerts.count} Active Alert
                                        {alerts.count !== 1 ? 's' : ''}
                                    </span>
                                </div>
                                <ul className="mt-2 space-y-1">
                                    {alerts.alerts.map((alert) => (
                                        <li
                                            key={alert.id}
                                            className="text-sm text-red-700 dark:text-red-300"
                                        >
                                            {alert.name} — {alert.metric}{' '}
                                            {alert.condition}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ) : null}

                        {/* Metric Cards */}
                        {metrics === null || metrics === undefined ? (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                {[...Array(4)].map((_, i) => (
                                    <SectionSkeleton key={i} />
                                ))}
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <MetricCard
                                    title="Requests"
                                    value={metrics.requests.total.toLocaleString()}
                                    subtitle={`${metrics.requests.error_rate}% error rate`}
                                    color="blue"
                                />
                                <MetricCard
                                    title="Exceptions"
                                    value={metrics.exceptions.total.toLocaleString()}
                                    subtitle={`${metrics.exceptions.unhandled} unhandled`}
                                    color={
                                        metrics.exceptions.unhandled > 0
                                            ? 'red'
                                            : 'green'
                                    }
                                />
                                <MetricCard
                                    title="Jobs"
                                    value={metrics.jobs.total.toLocaleString()}
                                    subtitle={`${metrics.jobs.failure_rate}% failure rate`}
                                    color={
                                        metrics.jobs.failed > 0
                                            ? 'orange'
                                            : 'green'
                                    }
                                />
                                <MetricCard
                                    title="Users"
                                    value={metrics.users.authenticated.toLocaleString()}
                                    subtitle="authenticated"
                                    color="purple"
                                />
                            </div>
                        )}

                        {/* Recent Issues */}
                        <div className="rounded-lg border bg-card">
                            <div className="flex items-center justify-between border border-b px-6 py-4">
                                <h2 className="text-base font-semibold text-foreground">
                                    Recent Issues
                                </h2>
                                {context && (
                                    <a
                                        href={issuesIndex.url(context.env)}
                                        className="text-sm text-primary hover:underline"
                                    >
                                        View all →
                                    </a>
                                )}
                            </div>
                            <div className="divide-y divide-border">
                                {recentIssues === null ||
                                recentIssues === undefined ? (
                                    <div className="p-6">
                                        <SectionSkeleton />
                                    </div>
                                ) : recentIssues.count === 0 ? (
                                    <div className="px-6 py-8 text-center text-sm text-muted-foreground">
                                        No open issues. 🎉
                                    </div>
                                ) : (
                                    recentIssues.issues.map((issue) => (
                                        <div
                                            key={issue.id}
                                            className="flex items-center justify-between px-6 py-3"
                                        >
                                            <div>
                                                <p className="text-sm font-medium text-foreground">
                                                    {issue.title}
                                                </p>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    <span
                                                        className={`mr-2 inline-block rounded px-1.5 py-0.5 text-xs ${
                                                            issue.priority ===
                                                            'critical'
                                                                ? 'bg-red-100 text-red-700'
                                                                : issue.priority ===
                                                                    'high'
                                                                  ? 'bg-orange-100 text-orange-700'
                                                                  : 'bg-muted text-foreground'
                                                        }`}
                                                    >
                                                        {issue.priority}
                                                    </span>
                                                    {issue.occurrence_count}{' '}
                                                    occurrence
                                                    {issue.occurrence_count !==
                                                    1
                                                        ? 's'
                                                        : ''}
                                                </p>
                                            </div>
                                            <span className="text-xs text-muted-foreground">
                                                {new Date(
                                                    issue.last_seen_at,
                                                ).toLocaleDateString()}
                                            </span>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
