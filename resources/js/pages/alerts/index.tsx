import { Head } from '@inertiajs/react';
import { create } from '@/actions/App/Http/Controllers/Alerts/AlertRuleController';
import AppLayout from '@/layouts/app-layout';

interface AlertRule {
    id: number;
    name: string;
    metric: string;
    operator: string;
    threshold: number;
    window_minutes: number;
    enabled: boolean;
}

interface Props {
    alertRules: AlertRule[];
    environment: { slug: string };
}

export default function AlertsIndex({
    alertRules,
    environment,
}: Props) {
    const createUrl = create.url(environment);

    return (
        <AppLayout breadcrumbs={[{ title: 'Alert Rules', href: '#' }]}>
            <Head title="Alert Rules" />
            <div className="p-6">
                <div className="mb-4 flex justify-between">
                    <h1 className="text-xl font-semibold">Alert Rules</h1>
                    <a
                        href={createUrl}
                        className="rounded bg-primary px-4 py-2 text-white"
                    >
                        Create Rule
                    </a>
                </div>
                {alertRules.length === 0 ? (
                    <p className="text-muted-foreground">
                        No alert rules configured.
                    </p>
                ) : (
                    <table className="w-full border-collapse">
                        <thead>
                            <tr className="border-b">
                                <th className="py-2 text-left">Name</th>
                                <th className="py-2 text-left">Metric</th>
                                <th className="py-2 text-left">Condition</th>
                                <th className="py-2 text-left">Window</th>
                                <th className="py-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {alertRules.map((rule) => (
                                <tr key={rule.id} className="border-b">
                                    <td className="py-2">{rule.name}</td>
                                    <td className="py-2">{rule.metric}</td>
                                    <td className="py-2">
                                        {rule.operator} {rule.threshold}
                                    </td>
                                    <td className="py-2">
                                        {rule.window_minutes}m
                                    </td>
                                    <td className="py-2">
                                        <span
                                            className={`rounded px-2 py-1 text-sm ${rule.enabled ? 'bg-green-100 text-green-800' : 'bg-muted text-muted-foreground'}`}
                                        >
                                            {rule.enabled
                                                ? 'Enabled'
                                                : 'Disabled'}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </AppLayout>
    );
}
