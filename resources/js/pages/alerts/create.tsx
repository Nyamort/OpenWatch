import { Head, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Alerts/AlertRuleController';
import AppLayout from '@/layouts/app-layout';

interface Member {
    id: number;
    name: string;
    email: string;
}

interface Props {
    environment: { slug: string };
    members: Member[];
}

export default function CreateAlertRule({ environment, members }: Props) {
    const baseUrl = store.url(environment);
    const { data, setData, post, errors } = useForm({
        name: '',
        metric: 'error_rate',
        operator: '>',
        threshold: '',
        window_minutes: '60',
        recipient_ids: [] as number[],
    });

    return (
        <AppLayout breadcrumbs={[{ title: 'Create Alert Rule', href: '#' }]}>
            <Head title="Create Alert Rule" />
            <div className="max-w-lg p-6">
                <h1 className="mb-4 text-xl font-semibold">
                    Create Alert Rule
                </h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(baseUrl);
                    }}
                >
                    <div className="mb-4">
                        <label className="mb-1 block text-sm font-medium">
                            Name
                        </label>
                        <input
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full rounded border px-3 py-2"
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.name}
                            </p>
                        )}
                    </div>
                    <div className="mb-4">
                        <label className="mb-1 block text-sm font-medium">
                            Metric
                        </label>
                        <select
                            value={data.metric}
                            onChange={(e) => setData('metric', e.target.value)}
                            className="w-full rounded border px-3 py-2"
                        >
                            <option value="error_rate">Error Rate (%)</option>
                            <option value="exception_count">
                                Exception Count
                            </option>
                            <option value="request_count">Request Count</option>
                            <option value="job_failure_rate">
                                Job Failure Rate (%)
                            </option>
                            <option value="p95_duration">
                                P95 Duration (ms)
                            </option>
                        </select>
                    </div>
                    <div className="mb-4 flex gap-2">
                        <div className="flex-1">
                            <label className="mb-1 block text-sm font-medium">
                                Operator
                            </label>
                            <select
                                value={data.operator}
                                onChange={(e) =>
                                    setData('operator', e.target.value)
                                }
                                className="w-full rounded border px-3 py-2"
                            >
                                <option value=">">{'>'}</option>
                                <option value=">=">{'>='}</option>
                                <option value="<">{'<'}</option>
                                <option value="<=">{'<='}</option>
                            </select>
                        </div>
                        <div className="flex-1">
                            <label className="mb-1 block text-sm font-medium">
                                Threshold
                            </label>
                            <input
                                type="number"
                                value={data.threshold}
                                onChange={(e) =>
                                    setData('threshold', e.target.value)
                                }
                                className="w-full rounded border px-3 py-2"
                            />
                        </div>
                    </div>
                    <div className="mb-4">
                        <label className="mb-1 block text-sm font-medium">
                            Window
                        </label>
                        <select
                            value={data.window_minutes}
                            onChange={(e) =>
                                setData('window_minutes', e.target.value)
                            }
                            className="w-full rounded border px-3 py-2"
                        >
                            <option value="5">5 minutes</option>
                            <option value="15">15 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="60">1 hour</option>
                            <option value="120">2 hours</option>
                            <option value="240">4 hours</option>
                            <option value="1440">24 hours</option>
                        </select>
                    </div>
                    <div className="mb-4">
                        <label className="mb-1 block text-sm font-medium">
                            Recipients
                        </label>
                        <select
                            multiple
                            value={data.recipient_ids.map(String)}
                            onChange={(e) =>
                                setData(
                                    'recipient_ids',
                                    Array.from(e.target.selectedOptions, (o) =>
                                        Number(o.value),
                                    ),
                                )
                            }
                            className="h-32 w-full rounded border px-3 py-2"
                        >
                            {members.map((m) => (
                                <option key={m.id} value={m.id}>
                                    {m.name} ({m.email})
                                </option>
                            ))}
                        </select>
                        {errors.recipient_ids && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.recipient_ids}
                            </p>
                        )}
                    </div>
                    <button
                        type="submit"
                        className="rounded bg-primary px-4 py-2 text-white"
                    >
                        Create Rule
                    </button>
                </form>
            </div>
        </AppLayout>
    );
}
