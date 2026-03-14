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
    { key: 'user', label: 'User' },
    { key: 'request_count', label: 'Requests' },
    { key: 'exception_count', label: 'Exceptions' },
    { key: 'job_count', label: 'Jobs' },
];

export default function UsersIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title="User Analytics" period={period}>
            <Head title="User Analytics" />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
