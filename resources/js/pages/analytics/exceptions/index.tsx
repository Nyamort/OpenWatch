import { DataTable } from '@/components/analytics/data-table';
import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: { period_label: string };
    rows: Array<Record<string, unknown>>;
    pagination?: { current_page: number; last_page: number; per_page: number; total: number } | null;
    filters_applied: { search?: string };
}

interface Props {
    analytics: Analytics;
    period: string;
}

const columns = [
    { key: 'class', label: 'Exception' },
    { key: 'total', label: 'Count' },
    { key: 'handled_count', label: 'Handled' },
    { key: 'unhandled_count', label: 'Unhandled' },
    { key: 'first_seen', label: 'First Seen' },
    { key: 'last_seen', label: 'Last Seen' },
];

export default function ExceptionsIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title="Exception Analytics" period={period}>
            <Head title="Exception Analytics" />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
