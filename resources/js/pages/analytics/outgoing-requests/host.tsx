import { DataTable } from '@/components/analytics/data-table';
import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: { host: string; period_label: string };
    rows: Array<Record<string, unknown>>;
    pagination?: { current_page: number; last_page: number; per_page: number; total: number } | null;
}

interface Props {
    analytics: Analytics;
    period: string;
}

const columns = [
    { key: 'method', label: 'Method' },
    { key: 'url', label: 'URL' },
    { key: 'status_code', label: 'Status' },
    { key: 'duration', label: 'Duration (ms)' },
    { key: 'recorded_at', label: 'Time' },
];

export default function OutgoingRequestsHost({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title={`Requests to ${analytics.summary.host}`} period={period}>
            <Head title={`Host: ${analytics.summary.host}`} />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
