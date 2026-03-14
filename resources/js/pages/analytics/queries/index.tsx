import { DataTable } from '@/components/analytics/data-table';
import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Row {
    sql_hash: string;
    sql_preview: string;
    total: number;
    avg_duration_ms: number;
    p95_duration_ms: number;
    max_duration_ms: number;
    [key: string]: unknown;
}

interface Analytics {
    summary: { period_label: string };
    rows: Row[];
    pagination?: { current_page: number; last_page: number; per_page: number; total: number } | null;
}

interface Props {
    analytics: Analytics;
    period: string;
}

const columns = [
    { key: 'sql_preview', label: 'Query' },
    { key: 'total', label: 'Count' },
    { key: 'avg_duration_ms', label: 'Avg (ms)' },
    { key: 'p95_duration_ms', label: 'P95 (ms)' },
    { key: 'max_duration_ms', label: 'Max (ms)' },
];

export default function QueriesIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title="Query Analytics" period={period}>
            <Head title="Query Analytics" />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
