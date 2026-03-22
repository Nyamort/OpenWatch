import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

interface Metrics {
    requests: { total: number; error_count: number; error_rate: number; p95_duration: number };
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
        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <p className="text-sm font-medium text-gray-500 dark:text-gray-400">{title}</p>
            <p className={`text-3xl font-bold mt-1 text-${color}-600 dark:text-${color}-400`}>{value}</p>
            {subtitle && <p className="text-sm text-gray-500 mt-1">{subtitle}</p>}
        </div>
    );
}

function SectionSkeleton() {
    return <div className="animate-pulse bg-gray-100 dark:bg-gray-700 rounded-lg h-24" />;
}

export default function Dashboard({ hasContext, period, context, metrics, alerts, recentIssues }: Props) {
    const periods = [
        { value: '1h', label: '1h' },
        { value: '24h', label: '24h' },
        { value: '7d', label: '7d' },
        { value: '14d', label: '14d' },
        { value: '30d', label: '30d' },
    ];

    const changePeriod = (p: string) => {
        const url = new URL(window.location.href);
        url.searchParams.set('period', p);
        window.location.href = url.toString();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Dashboard</h1>
                        {context && (
                            <p className="text-sm text-gray-500 mt-1">
                                {context.org} / {context.project} / {context.env}
                            </p>
                        )}
                    </div>
                    {/* Period selector */}
                    <div className="flex gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                        {periods.map((p) => (
                            <button
                                key={p.value}
                                onClick={() => changePeriod(p.value)}
                                className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                                    period === p.value
                                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                        : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                                }`}
                            >
                                {p.label}
                            </button>
                        ))}
                    </div>
                </div>

                {!hasContext ? (
                    <div className="text-center py-20 text-gray-500">
                        <p className="text-lg">No project configured yet.</p>
                        <p className="text-sm mt-2">Create an organization and project to start monitoring.</p>
                    </div>
                ) : (
                    <>
                        {/* Active Alerts Banner */}
                        {alerts === null || alerts === undefined ? (
                            <SectionSkeleton />
                        ) : alerts.count > 0 ? (
                            <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                <div className="flex items-center gap-2">
                                    <span className="text-red-600 dark:text-red-400 font-semibold">
                                        ⚠ {alerts.count} Active Alert{alerts.count !== 1 ? 's' : ''}
                                    </span>
                                </div>
                                <ul className="mt-2 space-y-1">
                                    {alerts.alerts.map((alert) => (
                                        <li key={alert.id} className="text-sm text-red-700 dark:text-red-300">
                                            {alert.name} — {alert.metric} {alert.condition}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ) : null}

                        {/* Metric Cards */}
                        {metrics === null || metrics === undefined ? (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                {[...Array(4)].map((_, i) => (
                                    <SectionSkeleton key={i} />
                                ))}
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                                    color={metrics.exceptions.unhandled > 0 ? 'red' : 'green'}
                                />
                                <MetricCard
                                    title="Jobs"
                                    value={metrics.jobs.total.toLocaleString()}
                                    subtitle={`${metrics.jobs.failure_rate}% failure rate`}
                                    color={metrics.jobs.failed > 0 ? 'orange' : 'green'}
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
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                <h2 className="text-base font-semibold text-gray-900 dark:text-white">Recent Issues</h2>
                                {context && (
                                    <a
                                        href={`/organizations/${context.org}/projects/${context.project}/environments/${context.env}/issues`}
                                        className="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                                    >
                                        View all →
                                    </a>
                                )}
                            </div>
                            <div className="divide-y divide-gray-100 dark:divide-gray-700">
                                {recentIssues === null || recentIssues === undefined ? (
                                    <div className="p-6">
                                        <SectionSkeleton />
                                    </div>
                                ) : recentIssues.count === 0 ? (
                                    <div className="px-6 py-8 text-center text-gray-500 text-sm">No open issues. 🎉</div>
                                ) : (
                                    recentIssues.issues.map((issue) => (
                                        <div key={issue.id} className="px-6 py-3 flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">{issue.title}</p>
                                                <p className="text-xs text-gray-500 mt-0.5">
                                                    <span
                                                        className={`inline-block px-1.5 py-0.5 rounded text-xs mr-2 ${
                                                            issue.priority === 'critical'
                                                                ? 'bg-red-100 text-red-700'
                                                                : issue.priority === 'high'
                                                                  ? 'bg-orange-100 text-orange-700'
                                                                  : 'bg-gray-100 text-gray-700'
                                                        }`}
                                                    >
                                                        {issue.priority}
                                                    </span>
                                                    {issue.occurrence_count} occurrence{issue.occurrence_count !== 1 ? 's' : ''}
                                                </p>
                                            </div>
                                            <span className="text-xs text-gray-400">
                                                {new Date(issue.last_seen_at).toLocaleDateString()}
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
