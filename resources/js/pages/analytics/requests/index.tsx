import { DataTable } from '@/components/analytics/data-table';
import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { usePage } from '@inertiajs/react';

interface Row {
    route_path: string;
    method: string;
    total: number;
    avg_duration: number;
    p95_duration: number;
    error_rate: number;
    [key: string]: unknown;
}

interface Analytics {
    summary: { total_requests: number; period_label: string };
    rows: Row[];
    pagination?: { current_page: number; last_page: number; per_page: number; total: number } | null;
    filters_applied: { sort?: string; direction?: string };
}

interface Props {
    analytics: Analytics;
    period: string;
}

const columns = [
    { key: 'route_path', label: 'Route', sortable: false },
    { key: 'method', label: 'Method', sortable: false },
    { key: 'total', label: 'Requests', sortable: true },
    { key: 'avg_duration', label: 'Avg (ms)', sortable: true },
    { key: 'p95_duration', label: 'P95 (ms)', sortable: true },
    {
        key: 'error_rate',
        label: 'Error Rate',
        sortable: true,
        render: (value: unknown) => `${value}%`,
    },
];

export default function RequestsIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title="Request Analytics" period={period}>
            <Head title="Request Analytics" />
            <div className="rounded-lg border bg-card p-4">
                <p className="text-sm text-muted-foreground">
                    Total requests: <span className="font-medium text-foreground">{analytics.summary.total_requests}</span>
                </p>
            </div>
            <DataTable
                columns={columns}
                rows={analytics.rows}
                pagination={analytics.pagination}
                sortKey={analytics.filters_applied.sort}
                sortDirection={analytics.filters_applied.direction}
            />
        </AnalyticsLayout>
    );
}
