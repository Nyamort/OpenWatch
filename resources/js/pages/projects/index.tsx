import { Head, Link, useForm } from '@inertiajs/react';
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
    description: string | null;
    archived_at: string | null;
}

interface Props {
    organization: Organization;
    projects: Project[];
}

export default function ProjectsIndex({ organization, projects }: Props) {
    const [showCreateForm, setShowCreateForm] = useState(false);

    const baseUrl = `/organizations/${organization.slug}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: organization.name, href: baseUrl },
        { title: 'Applications', href: `${baseUrl}/projects` },
    ];

    const form = useForm({
        name: '',
        slug: '',
        description: '',
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
        form.post(`${baseUrl}/projects`, {
            onSuccess: () => {
                form.reset();
                setShowCreateForm(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Applications" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold text-foreground">Applications</h1>
                    <Button onClick={() => setShowCreateForm((v) => !v)}>
                        {showCreateForm ? 'Cancel' : 'Create Application'}
                    </Button>
                </div>

                {/* Create form */}
                {showCreateForm && (
                    <div className="rounded-lg border bg-card p-6">
                        <h2 className="text-base font-semibold mb-4 text-foreground">New Application</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="project-name">Name</Label>
                                <Input
                                    id="project-name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={handleNameChange}
                                    placeholder="My Application"
                                    required
                                />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="project-slug">Slug</Label>
                                <Input
                                    id="project-slug"
                                    type="text"
                                    value={form.data.slug}
                                    onChange={(e) => form.setData('slug', e.target.value)}
                                    placeholder="my-application"
                                    required
                                />
                                <InputError message={form.errors.slug} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="project-description">Description</Label>
                                <Input
                                    id="project-description"
                                    type="text"
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    placeholder="Optional description"
                                />
                                <InputError message={form.errors.description} />
                            </div>

                            <div className="flex gap-3">
                                <Button type="submit" disabled={form.processing}>
                                    Create Application
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

                {/* Project list */}
                {projects.length === 0 ? (
                    <div className="rounded-lg border border-dashed border  p-12 text-center">
                        <p className="text-muted-foreground">No applications yet.</p>
                        <p className="text-sm text-muted-foreground mt-1">Create your first application to get started.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        {projects.map((project) => (
                            <Link
                                key={project.id}
                                href={`${baseUrl}/projects/${project.slug}`}
                                className="block rounded-lg border bg-card p-5 hover:border  hover:shadow-sm transition-all"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <p className="font-medium text-foreground truncate">{project.name}</p>
                                        <p className="text-sm text-muted-foreground truncate">{project.slug}</p>
                                    </div>
                                    {project.archived_at && (
                                        <Badge variant="outline" className="shrink-0">Archived</Badge>
                                    )}
                                </div>
                                {project.description && (
                                    <p className="text-sm text-muted-foreground mt-2 line-clamp-2">
                                        {project.description}
                                    </p>
                                )}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
