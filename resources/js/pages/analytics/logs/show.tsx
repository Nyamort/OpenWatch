import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

interface Analytics {
    summary: Record<string, unknown>;
}

interface Props {
    analytics: Analytics;
}

export default function LogShow({ analytics }: Props) {
    const log = analytics.summary;

    return (
        <AppLayout>
            <Head />
            <div className="flex flex-col gap-6 p-6">
                <h1 className="text-xl font-semibold">Log Detail</h1>
                <div className="rounded-lg border bg-card p-4">
                    <dl className="space-y-2 text-sm">
                        {Object.entries(log).map(([k, v]) => (
                            <div key={k} className="grid grid-cols-4 gap-2">
                                <dt className="text-muted-foreground">{k}</dt>
                                <dd className="col-span-3 font-medium break-all">
                                    {typeof v === 'object' ? JSON.stringify(v, null, 2) : String(v ?? '')}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </div>
        </AppLayout>
    );
}
