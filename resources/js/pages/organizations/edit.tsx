import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
    timezone: string;
    locale: string;
    logo_url?: string | null;
}

interface Props {
    organization: Organization;
}

export default function OrganizationsEdit({ organization }: Props) {
    const baseUrl = `/organizations/${organization.slug}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Organizations', href: '/organizations' },
        { title: organization.name, href: baseUrl },
        { title: 'Edit', href: `${baseUrl}/edit` },
    ];

    const form = useForm({
        name: organization.name,
        slug: organization.slug,
        timezone: organization.timezone ?? '',
        locale: organization.locale ?? '',
        logo_url: organization.logo_url ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(baseUrl);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${organization.name}`} />
            <div className="flex flex-col gap-6 p-6 max-w-lg">
                <h1 className="text-2xl font-semibold text-foreground">Edit Organization</h1>

                <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            type="text"
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
                            type="text"
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
                            type="text"
                            value={form.data.timezone}
                            onChange={(e) => form.setData('timezone', e.target.value)}
                            placeholder="UTC"
                        />
                        <InputError message={form.errors.timezone} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="locale">Locale</Label>
                        <Input
                            id="locale"
                            type="text"
                            value={form.data.locale}
                            onChange={(e) => form.setData('locale', e.target.value)}
                            placeholder="en"
                        />
                        <InputError message={form.errors.locale} />
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

                    <div className="flex gap-3">
                        <Button type="submit" disabled={form.processing}>
                            Save Changes
                        </Button>
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
