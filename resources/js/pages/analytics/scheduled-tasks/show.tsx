import { DataTable } from '@/components/analytics/data-table';
import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: { name: string; cron: string; period_label: string };
    rows: Array<Record<string, unknown>>;
    pagination?: { current_page: number; last_page: number; per_page: number; total: number } | null;
}

interface Props {
    analytics: Analytics;
    period: string;
}

const columns = [
    { key: 'status', label: 'Status' },
    { key: 'duration', label: 'Duration (ms)' },
    { key: 'recorded_at', label: 'Time' },
];

export default function ScheduledTaskShow({ analytics, period }: Props) {
    return (
        <AnalyticsLayout period={period}>
            <Head />
            <div className="rounded-lg border bg-card p-4 text-sm">
                <span className="text-muted-foreground">Schedule: </span>
                <code className="font-mono">{analytics.summary.cron}</code>
            </div>
            <DataTable columns={columns} rows={analytics.rows} pagination={analytics.pagination} />
        </AnalyticsLayout>
    );
}
