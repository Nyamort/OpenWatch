import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

interface Analytics {
    summary: Record<string, unknown>;
    rows: {
        queries: unknown[];
        exceptions: unknown[];
        logs: unknown[];
    };
}

interface Props {
    analytics: Analytics;
}

export default function RequestShow({ analytics }: Props) {
    const req = analytics.summary;

    return (
        <AppLayout>
            <Head />
            <div className="flex flex-col gap-6 p-6">
                <h1 className="text-xl font-semibold">Request Detail</h1>
                <div className="rounded-lg border bg-card p-4">
                    <dl className="grid grid-cols-2 gap-4 text-sm">
                        {Object.entries(req).map(([k, v]) => (
                            <div key={k}>
                                <dt className="text-muted-foreground">{k}</dt>
                                <dd className="font-medium">{String(v ?? '')}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
                <section>
                    <h2 className="mb-2 text-sm font-medium">Queries ({analytics.rows.queries.length})</h2>
                </section>
                <section>
                    <h2 className="mb-2 text-sm font-medium">Exceptions ({analytics.rows.exceptions.length})</h2>
                </section>
                <section>
                    <h2 className="mb-2 text-sm font-medium">Logs ({analytics.rows.logs.length})</h2>
                </section>
            </div>
        </AppLayout>
    );
}
