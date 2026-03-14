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
    { key: 'name', label: 'Command' },
    { key: 'total', label: 'Total' },
    { key: 'success_count', label: 'Success' },
    { key: 'failed_count', label: 'Failed' },
    { key: 'pending_count', label: 'Pending' },
    { key: 'avg_duration', label: 'Avg Duration (ms)' },
];

export default function CommandsIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title="Command Analytics" period={period}>
            <Head title="Command Analytics" />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
