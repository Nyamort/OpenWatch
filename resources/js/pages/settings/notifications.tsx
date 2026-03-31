import { Head, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import NotificationPreferencesController from '@/actions/App/Http/Controllers/Settings/NotificationPreferencesController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/notifications';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notification settings',
        href: edit().url,
    },
];

const CATEGORY_LABELS: Record<string, string> = {
    issue_updates: 'Issue Updates',
    threshold_alerts: 'Threshold Alerts',
    security: 'Security',
};

const CATEGORY_DESCRIPTIONS: Record<string, string> = {
    issue_updates: 'Receive notifications when issues are created or updated.',
    threshold_alerts: 'Receive alerts when monitored thresholds are exceeded.',
    security:
        'Important security notifications about your account. Cannot be disabled.',
};

type CategoryPreference = {
    enabled: boolean;
    locked: boolean;
};

type NotificationsProps = {
    categories: Record<string, CategoryPreference>;
    status?: string;
};

export default function Notifications({
    categories,
    status,
}: NotificationsProps) {
    const initialData: Record<string, { enabled: boolean }> = {};

    for (const [category, pref] of Object.entries(categories)) {
        initialData[category] = { enabled: pref.enabled };
    }

    const { data, setData, processing, patch } = useForm<{
        categories: Record<string, { enabled: boolean }>;
    }>({
        categories: initialData,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        patch(NotificationPreferencesController.update.url(), {
            onSuccess: () => toast.success('Notifications updated'),
        });
    }

    function handleToggle(category: string, value: boolean) {
        setData('categories', {
            ...data.categories,
            [category]: { enabled: value },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notification settings" />

            <h1 className="sr-only">Notification Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Notification preferences"
                        description="Control which notifications you receive"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            {Object.entries(categories).map(
                                ([category, pref]) => (
                                    <div
                                        key={category}
                                        className="flex items-start justify-between gap-4 rounded-lg border p-4"
                                    >
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <Label
                                                    htmlFor={`category-${category}`}
                                                >
                                                    {CATEGORY_LABELS[
                                                        category
                                                    ] ?? category}
                                                </Label>

                                                {pref.locked && (
                                                    <span className="text-xs text-muted-foreground">
                                                        🔒
                                                    </span>
                                                )}
                                            </div>

                                            <p className="text-sm text-muted-foreground">
                                                {CATEGORY_DESCRIPTIONS[
                                                    category
                                                ] ?? ''}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            id={`category-${category}`}
                                            role="switch"
                                            aria-checked={
                                                data.categories[category]
                                                    ?.enabled ?? pref.enabled
                                            }
                                            disabled={pref.locked}
                                            onClick={() =>
                                                handleToggle(
                                                    category,
                                                    !(
                                                        data.categories[
                                                            category
                                                        ]?.enabled ??
                                                        pref.enabled
                                                    ),
                                                )
                                            }
                                            className={[
                                                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-offset-2 focus:outline-none',
                                                'focus:ring-ring',
                                                (data.categories[category]
                                                    ?.enabled ?? pref.enabled)
                                                    ? 'bg-primary'
                                                    : 'bg-input',
                                                pref.locked
                                                    ? 'cursor-not-allowed opacity-50'
                                                    : '',
                                            ]
                                                .filter(Boolean)
                                                .join(' ')}
                                        >
                                            <span
                                                className={[
                                                    'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                    (data.categories[category]
                                                        ?.enabled ??
                                                    pref.enabled)
                                                        ? 'translate-x-5'
                                                        : 'translate-x-0',
                                                ].join(' ')}
                                            />
                                        </button>
                                    </div>
                                ),
                            )}
                        </div>

                        <div className="flex items-center gap-4">
                            <Button
                                disabled={processing}
                                data-test="update-notifications-button"
                            >
                                Save
                            </Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
