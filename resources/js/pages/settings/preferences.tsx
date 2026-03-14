import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';
import PreferencesController from '@/actions/App/Http/Controllers/Settings/PreferencesController';
import { edit } from '@/routes/preferences';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Preferences settings',
        href: edit().url,
    },
];

const TIMEZONES = [
    'UTC',
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Los_Angeles',
    'America/Toronto',
    'America/Vancouver',
    'Europe/London',
    'Europe/Paris',
    'Europe/Berlin',
    'Europe/Amsterdam',
    'Asia/Tokyo',
    'Asia/Shanghai',
    'Asia/Kolkata',
    'Asia/Dubai',
    'Australia/Sydney',
    'Pacific/Auckland',
];

const LOCALES: { value: string; label: string }[] = [
    { value: 'en', label: 'English' },
    { value: 'fr', label: 'French' },
];

export default function Preferences({
    timezone,
    locale,
    status,
}: {
    timezone: string;
    locale: string;
    status?: string;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Preferences settings" />

            <h1 className="sr-only">Preferences Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Preferences"
                        description="Update your timezone and language preferences"
                    />

                    <Form
                        {...PreferencesController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="timezone">Timezone</Label>

                                    <select
                                        id="timezone"
                                        name="timezone"
                                        defaultValue={timezone}
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring mt-1 flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {TIMEZONES.map((tz) => (
                                            <option key={tz} value={tz}>
                                                {tz}
                                            </option>
                                        ))}
                                    </select>

                                    <InputError
                                        className="mt-2"
                                        message={errors.timezone}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="locale">Language</Label>

                                    <select
                                        id="locale"
                                        name="locale"
                                        defaultValue={locale}
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring mt-1 flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {LOCALES.map((l) => (
                                            <option key={l.value} value={l.value}>
                                                {l.label}
                                            </option>
                                        ))}
                                    </select>

                                    <InputError
                                        className="mt-2"
                                        message={errors.locale}
                                    />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-preferences-button"
                                    >
                                        Save
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            Saved
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
