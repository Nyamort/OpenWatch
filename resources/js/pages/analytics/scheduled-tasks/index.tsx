import { Head } from '@inertiajs/react';
import { DataTable } from '@/components/analytics/data-table';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: { period_label: string };
    rows: Array<Record<string, unknown>>;
    pagination?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    } | null;
}

interface Props {
    analytics: Analytics;
    period: string;
}

const columns = [
    { key: 'name', label: 'Task' },
    { key: 'cron', label: 'Schedule' },
    { key: 'total', label: 'Total' },
    { key: 'processed_count', label: 'Processed' },
    { key: 'skipped_count', label: 'Skipped' },
    { key: 'failed_count', label: 'Failed' },
    { key: 'avg_duration', label: 'Avg Duration (ms)' },
];

export default function ScheduledTasksIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout period={period}>
            <Head />
            <DataTable
                columns={columns}
                rows={analytics.rows}
                pagination={analytics.pagination}
            />
        </AnalyticsLayout>
    );
}
