import { DataTable } from '@/components/analytics/data-table';
import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: {
        class: string;
        message: string;
        file: string;
        line: number;
        handled: boolean;
        related_requests: unknown[];
        [key: string]: unknown;
    };
    rows: Array<Record<string, unknown>>;
    pagination?: { current_page: number; last_page: number; per_page: number; total: number } | null;
}

interface Props {
    analytics: Analytics;
    period: string;
}

const occurrenceColumns = [
    { key: 'user', label: 'User' },
    { key: 'php_version', label: 'PHP' },
    { key: 'laravel_version', label: 'Laravel' },
    { key: 'recorded_at', label: 'Time' },
];

export default function ExceptionShow({ analytics, period }: Props) {
    const { summary } = analytics;

    return (
        <AnalyticsLayout period={period}>
            <Head />
            <div className="rounded-lg border bg-card p-4 text-sm space-y-2">
                <p className="text-muted-foreground">
                    {summary.file}:{summary.line}
                </p>
                <p className="font-medium">{summary.message}</p>
                <p>
                    <span className={summary.handled ? 'text-yellow-600' : 'text-red-600'}>
                        {summary.handled ? 'Handled' : 'Unhandled'}
                    </span>
                </p>
            </div>
            <section>
                <h2 className="mb-2 text-sm font-medium">Occurrences</h2>
                <DataTable columns={occurrenceColumns} rows={analytics.rows} pagination={analytics.pagination} />
            </section>
        </AnalyticsLayout>
    );
}
