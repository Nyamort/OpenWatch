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
    { key: 'class', label: 'Class' },
    { key: 'channel_group', label: 'Channel' },
    { key: 'total', label: 'Total' },
    { key: 'sent_count', label: 'Sent' },
    { key: 'failed_count', label: 'Failed' },
    { key: 'failed_rate', label: 'Fail Rate %' },
];

export default function NotificationsIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title="Notification Analytics" period={period}>
            <Head title="Notification Analytics" />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
