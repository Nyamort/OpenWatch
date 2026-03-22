import { Head } from '@inertiajs/react';
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
    organization: { slug: string };
    project: { slug: string };
    environment: { slug: string };
}

export default function AlertsIndex({ alertRules, organization, project, environment }: Props) {
    const createUrl = `/organizations/${organization.slug}/projects/${project.slug}/environments/${environment.slug}/alert-rules/create`;

    return (
        <AppLayout breadcrumbs={[{ title: 'Alert Rules', href: '#' }]}>
            <Head title="Alert Rules" />
            <div className="p-6">
                <div className="flex justify-between mb-4">
                    <h1 className="text-xl font-semibold">Alert Rules</h1>
                    <a href={createUrl} className="px-4 py-2 bg-primary text-white rounded">Create Rule</a>
                </div>
                {alertRules.length === 0 ? (
                    <p className="text-muted-foreground">No alert rules configured.</p>
                ) : (
                    <table className="w-full border-collapse">
                        <thead>
                            <tr className="border-b">
                                <th className="text-left py-2">Name</th>
                                <th className="text-left py-2">Metric</th>
                                <th className="text-left py-2">Condition</th>
                                <th className="text-left py-2">Window</th>
                                <th className="text-left py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {alertRules.map(rule => (
                                <tr key={rule.id} className="border-b">
                                    <td className="py-2">{rule.name}</td>
                                    <td className="py-2">{rule.metric}</td>
                                    <td className="py-2">{rule.operator} {rule.threshold}</td>
                                    <td className="py-2">{rule.window_minutes}m</td>
                                    <td className="py-2">
                                        <span className={`px-2 py-1 rounded text-sm ${rule.enabled ? 'bg-green-100 text-green-800' : 'bg-muted text-muted-foreground'}`}>
                                            {rule.enabled ? 'Enabled' : 'Disabled'}
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
