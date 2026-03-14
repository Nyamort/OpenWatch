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
    { key: 'name', label: 'Job' },
    { key: 'queue', label: 'Queue' },
    { key: 'total_attempts', label: 'Attempts' },
    { key: 'processed_count', label: 'Processed' },
    { key: 'failed_count', label: 'Failed' },
    { key: 'avg_duration', label: 'Avg Duration (ms)' },
];

export default function JobsIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title="Job Analytics" period={period}>
            <Head title="Job Analytics" />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
