import { DataTable } from '@/components/analytics/data-table';
import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Row {
    store: string;
    key: string;
    total_ops: number;
    hit_count: number;
    miss_count: number;
    hit_rate_pct: number;
    hit_rate_color: 'green' | 'yellow' | 'red';
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

const COLOR_MAP = {
    green: 'text-green-600',
    yellow: 'text-yellow-600',
    red: 'text-red-600',
} as const;

const columns = [
    { key: 'store', label: 'Store' },
    { key: 'key', label: 'Key' },
    { key: 'total_ops', label: 'Total Ops' },
    { key: 'hit_count', label: 'Hits' },
    { key: 'miss_count', label: 'Misses' },
    {
        key: 'hit_rate_pct',
        label: 'Hit Rate',
        render: (value: unknown, row: Row) => (
            <span className={COLOR_MAP[row.hit_rate_color] ?? ''}>{String(value)}%</span>
        ),
    },
];

export default function CacheEventsIndex({ analytics, period }: Props) {
    return (
        <AnalyticsLayout title="Cache Event Analytics" period={period}>
            <Head title="Cache Event Analytics" />
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
