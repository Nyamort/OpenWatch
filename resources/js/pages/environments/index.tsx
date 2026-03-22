import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface Project {
    id: number;
    name: string;
    slug: string;
}

interface Environment {
    id: number;
    name: string;
    slug: string;
    type: string;
    status: string;
}

interface Props {
    organization: Organization;
    project: Project;
    environments: Environment[];
}

const environmentTypes = ['production', 'staging', 'development', 'custom'];

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'active') {
        return 'secondary';
    }
    if (status === 'inactive') {
        return 'outline';
    }
    return 'outline';
}

export default function EnvironmentsIndex({ organization, project, environments }: Props) {
    const [showCreateForm, setShowCreateForm] = useState(false);

    const orgUrl = `/organizations/${organization.slug}`;
    const projectUrl = `${orgUrl}/projects/${project.slug}`;
    const environmentsUrl = `${projectUrl}/environments`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: organization.name, href: orgUrl },
        { title: project.name, href: projectUrl },
        { title: 'Environments', href: environmentsUrl },
    ];

    const form = useForm({
        name: '',
        slug: '',
        type: 'production',
    });

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

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(environmentsUrl, {
            onSuccess: () => {
                form.reset();
                setShowCreateForm(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Environments" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Environments</h1>
                    <Button onClick={() => setShowCreateForm((v) => !v)}>
                        {showCreateForm ? 'Cancel' : 'Create Environment'}
                    </Button>
                </div>

                {/* Create form */}
                {showCreateForm && (
                    <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                        <h2 className="text-base font-semibold mb-4 text-gray-900 dark:text-white">New Environment</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="env-name">Name</Label>
                                <Input
                                    id="env-name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={handleNameChange}
                                    placeholder="Production"
                                    required
                                />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="env-slug">Slug</Label>
                                <Input
                                    id="env-slug"
                                    type="text"
                                    value={form.data.slug}
                                    onChange={(e) => form.setData('slug', e.target.value)}
                                    placeholder="production"
                                    required
                                />
                                <InputError message={form.errors.slug} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="env-type">Type</Label>
                                <select
                                    id="env-type"
                                    className="rounded-md border bg-background px-3 py-2 text-sm"
                                    value={form.data.type}
                                    onChange={(e) => form.setData('type', e.target.value)}
                                >
                                    {environmentTypes.map((type) => (
                                        <option key={type} value={type}>
                                            {type.charAt(0).toUpperCase() + type.slice(1)}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.type} />
                            </div>

                            <div className="flex gap-3">
                                <Button type="submit" disabled={form.processing}>
                                    Create Environment
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        form.reset();
                                        setShowCreateForm(false);
                                    }}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Environment list */}
                {environments.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center">
                        <p className="text-gray-500 dark:text-gray-400">No environments yet.</p>
                        <p className="text-sm text-gray-400 mt-1">Create an environment to start collecting data.</p>
                    </div>
                ) : (
                    <div className="rounded-lg border border-gray-200 dark:border-gray-700">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">Name</th>
                                    <th className="px-4 py-3 text-left font-medium">Slug</th>
                                    <th className="px-4 py-3 text-left font-medium">Type</th>
                                    <th className="px-4 py-3 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {environments.map((env) => (
                                    <tr key={env.id} className="hover:bg-muted/20">
                                        <td className="px-4 py-3">
                                            <a
                                                href={`${environmentsUrl}/${env.slug}`}
                                                className="font-medium hover:underline"
                                            >
                                                {env.name}
                                            </a>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">{env.slug}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant="outline" className="capitalize">{env.type}</Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={statusVariant(env.status)} className="capitalize">{env.status}</Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
