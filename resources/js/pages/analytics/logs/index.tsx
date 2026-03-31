import { Head } from '@inertiajs/react';
import { DataTable } from '@/components/analytics/data-table';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: { period_label: string };
    rows: Array<Record<string, unknown>>;
    pagination?: { current_page: number; last_page: number; per_page: number; total: number } | null;
    config: { levels: string[] };
    filters_applied: { level?: string; search?: string };
}

interface Props {
    analytics: Analytics;
    period: string;
}

const LEVEL_COLORS: Record<string, string> = {
    emergency: 'text-red-700 font-bold',
    alert: 'text-red-600 font-bold',
    critical: 'text-red-500',
    error: 'text-red-400',
    warning: 'text-yellow-500',
    notice: 'text-blue-500',
    info: 'text-green-500',
    debug: 'text-muted-foreground',
};

const columns = [
    {
        key: 'level',
        label: 'Level',
        render: (value: unknown) => (
            <span className={LEVEL_COLORS[String(value)] ?? ''}>{String(value)}</span>
        ),
    },
    { key: 'message', label: 'Message' },
    { key: 'recorded_at', label: 'Time' },
];

export default function LogsIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout period={period}>
            <Head />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
