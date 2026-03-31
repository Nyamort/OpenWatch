import { Head } from '@inertiajs/react';
import { DataTable } from '@/components/analytics/data-table';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: {
        sql_hash: string;
        sql_preview: string;
        total: number;
        avg_duration_ms: number;
        p95_duration_ms: number;
        max_duration_ms: number;
        period_label: string;
    };
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
    { key: 'connection', label: 'Connection' },
    { key: 'duration', label: 'Duration (µs)' },
    { key: 'recorded_at', label: 'Recorded At' },
];

export default function QueryShow({ analytics, period }: Props) {
    return (
        <AnalyticsLayout period={period}>
            <Head />
            <div className="rounded-lg border bg-card p-4">
                <pre className="overflow-x-auto text-xs">
                    {analytics.summary.sql_preview}
                </pre>
                <div className="mt-4 grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p className="text-muted-foreground">Total</p>
                        <p className="font-medium">{analytics.summary.total}</p>
                    </div>
                    <div>
                        <p className="text-muted-foreground">Avg (ms)</p>
                        <p className="font-medium">
                            {analytics.summary.avg_duration_ms}
                        </p>
                    </div>
                    <div>
                        <p className="text-muted-foreground">P95 (ms)</p>
                        <p className="font-medium">
                            {analytics.summary.p95_duration_ms}
                        </p>
                    </div>
                </div>
            </div>
            <DataTable
                columns={columns}
                rows={analytics.rows}
                pagination={analytics.pagination}
            />
        </AnalyticsLayout>
    );
}
