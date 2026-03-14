import { DataTable } from '@/components/analytics/data-table';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

interface Analytics {
    summary: Record<string, unknown>;
    rows: Array<Record<string, unknown>>;
}

interface Props {
    analytics: Analytics;
}

const attemptColumns = [
    { key: 'attempt', label: '#' },
    { key: 'status', label: 'Status' },
    { key: 'duration', label: 'Duration (ms)' },
    { key: 'recorded_at', label: 'Time' },
];

export default function JobShow({ analytics }: Props) {
    return (
        <AppLayout>
            <Head title="Job Detail" />
            <div className="flex flex-col gap-6 p-6">
                <h1 className="text-xl font-semibold">Job Detail</h1>
                <div className="rounded-lg border bg-card p-4">
                    <dl className="space-y-2 text-sm">
                        {Object.entries(analytics.summary).map(([k, v]) => (
                            <div key={k} className="grid grid-cols-4 gap-2">
                                <dt className="text-muted-foreground">{k}</dt>
                                <dd className="col-span-3 font-medium">{String(v ?? '')}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
                <section>
                    <h2 className="mb-2 text-sm font-medium">Attempts</h2>
                    <DataTable columns={attemptColumns} rows={analytics.rows} />
                </section>
            </div>
        </AppLayout>
    );
}
