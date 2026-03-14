import { DataTable } from '@/components/analytics/data-table';
import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: { period_label: string };
    rows: Array<Record<string, unknown>>;
    pagination?: { current_page: number; last_page: number; per_page: number; total: number } | null;
}

interface Props {
    analytics: Analytics;
    period: string;
}

const columns = [
    { key: 'host', label: 'Host' },
    { key: 'total', label: 'Total' },
    { key: 'count_2xx', label: '2xx' },
    { key: 'count_4xx', label: '4xx' },
    { key: 'count_5xx', label: '5xx' },
    { key: 'error_count', label: 'Errors' },
    { key: 'avg_duration', label: 'Avg (ms)' },
    { key: 'p95_duration', label: 'P95 (ms)' },
];

export default function OutgoingRequestsIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title="Outgoing Request Analytics" period={period}>
            <Head title="Outgoing Request Analytics" />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
