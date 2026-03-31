import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Analytics {
    summary: {
        route_path: string;
        method: string;
        total: number;
        avg_duration: number;
        p95_duration: number;
        error_rate: number;
        period_label: string;
    };
    series: Array<{ bucket: string; count: number; avg_duration: number }>;
}

interface Props {
    analytics: Analytics;
    period: string;
}

export default function RequestsRoute({ analytics, period }: Props) {
    const { summary } = analytics;

    return (
        <AnalyticsLayout period={period}>
            <Head />
            <div className="grid grid-cols-4 gap-4">
                {[
                    { label: 'Total', value: summary.total },
                    { label: 'Avg (ms)', value: summary.avg_duration },
                    { label: 'P95 (ms)', value: summary.p95_duration },
                    { label: 'Error Rate', value: `${summary.error_rate}%` },
                ].map((stat) => (
                    <div
                        key={stat.label}
                        className="rounded-lg border bg-card p-4"
                    >
                        <p className="text-xs text-muted-foreground">
                            {stat.label}
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {stat.value}
                        </p>
                    </div>
                ))}
            </div>
            <div className="rounded-lg border bg-card p-4">
                <h2 className="mb-4 text-sm font-medium">
                    Timeline ({analytics.series.length} buckets)
                </h2>
                {analytics.series.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No data in this period.
                    </p>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Chart data available ({analytics.series.length} points).
                    </p>
                )}
            </div>
        </AnalyticsLayout>
    );
}
