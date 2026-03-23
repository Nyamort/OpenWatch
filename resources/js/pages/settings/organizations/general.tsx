import { Head, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
    timezone: string;
    logo_url?: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organization settings', href: '#' },
];

export default function OrganizationGeneral({ organization }: { organization: Organization }) {
    const form = useForm({
        name: organization.name,
        slug: organization.slug,
        timezone: organization.timezone ?? '',
        logo_url: organization.logo_url ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(`/settings/organizations/${organization.slug}`, {
            onSuccess: () => toast.success('Organization updated'),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organization settings" />

            <h1 className="sr-only">Organization Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="General"
                        description="Update your organization's name and details"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={form.data.slug}
                                onChange={(e) => form.setData('slug', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.slug} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="timezone">Timezone</Label>
                            <Input
                                id="timezone"
                                value={form.data.timezone}
                                onChange={(e) => form.setData('timezone', e.target.value)}
                                placeholder="UTC"
                            />
                            <InputError message={form.errors.timezone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="logo_url">Logo URL</Label>
                            <Input
                                id="logo_url"
                                type="url"
                                value={form.data.logo_url}
                                onChange={(e) => form.setData('logo_url', e.target.value)}
                                placeholder="https://example.com/logo.png"
                            />
                            <InputError message={form.errors.logo_url} />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={form.processing}>Save</Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
