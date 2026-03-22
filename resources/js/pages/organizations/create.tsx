import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: '/organizations' },
    { title: 'Create Organization', href: '/organizations/create' },
];

export default function OrganizationsCreate() {
    const form = useForm({
        name: '',
        slug: '',
        timezone: '',
        locale: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/organizations');
    }

    function handleNameChange(e: React.ChangeEvent<HTMLInputElement>) {
        const name = e.target.value;
        form.setData('name', name);
        if (!form.data.slug || form.data.slug === slugify(form.data.name)) {
            form.setData('slug', slugify(name));
        }
    }

    function slugify(value: string): string {
        return value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Organization" />
            <div className="flex flex-col gap-6 p-6 max-w-lg">
                <h1 className="text-2xl font-semibold text-foreground">Create Organization</h1>

                <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            type="text"
                            value={form.data.name}
                            onChange={handleNameChange}
                            placeholder="Acme Corp"
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
                            placeholder="acme-corp"
                            required
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="timezone">Timezone <span className="text-muted-foreground font-normal">(optional)</span></Label>
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
                        <Label htmlFor="locale">Locale <span className="text-muted-foreground font-normal">(optional)</span></Label>
                        <Input
                            id="locale"
                            type="text"
                            value={form.data.locale}
                            onChange={(e) => form.setData('locale', e.target.value)}
                            placeholder="en"
                        />
                        <InputError message={form.errors.locale} />
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={form.processing}>
                            Create Organization
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
