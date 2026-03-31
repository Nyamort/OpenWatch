import { Head } from '@inertiajs/react';
import { DataTable } from '@/components/analytics/data-table';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: {
        user: string;
        request_count: number;
        exception_count: number;
        job_count: number;
        period_label: string;
    };
    rows: {
        requests: Array<Record<string, unknown>>;
        exceptions: Array<Record<string, unknown>>;
        jobs: Array<Record<string, unknown>>;
    };
}

interface Props {
    analytics: Analytics;
    user_value: string;
    period: string;
}

const requestColumns = [
    { key: 'method', label: 'Method' },
    { key: 'route_path', label: 'Route' },
    { key: 'status_code', label: 'Status' },
    { key: 'duration', label: 'Duration (ms)' },
    { key: 'recorded_at', label: 'Time' },
];

const exceptionColumns = [
    { key: 'class', label: 'Exception' },
    { key: 'message', label: 'Message' },
    { key: 'recorded_at', label: 'Time' },
];

const jobColumns = [
    { key: 'name', label: 'Job' },
    { key: 'status', label: 'Status' },
    { key: 'recorded_at', label: 'Time' },
];

export default function UserShow({ analytics, user_value, period }: Props) {
    return (
        <AnalyticsLayout period={period}>
            <Head />
            <div className="grid grid-cols-3 gap-4">
                {[
                    { label: 'Requests', value: analytics.summary.request_count },
                    { label: 'Exceptions', value: analytics.summary.exception_count },
                    { label: 'Jobs', value: analytics.summary.job_count },
                ].map((stat) => (
                    <div key={stat.label} className="rounded-lg border bg-card p-4">
                        <p className="text-xs text-muted-foreground">{stat.label}</p>
                        <p className="mt-1 text-2xl font-semibold">{stat.value}</p>
                    </div>
                ))}
            </div>
            <section>
                <h2 className="mb-2 text-sm font-medium">Requests</h2>
                <DataTable columns={requestColumns} rows={analytics.rows.requests} />
            </section>
            <section>
                <h2 className="mb-2 text-sm font-medium">Exceptions</h2>
                <DataTable columns={exceptionColumns} rows={analytics.rows.exceptions} />
            </section>
            <section>
                <h2 className="mb-2 text-sm font-medium">Jobs</h2>
                <DataTable columns={jobColumns} rows={analytics.rows.jobs} />
            </section>
        </AnalyticsLayout>
    );
}
