import { Head, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { cn } from '@/lib/utils';
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
}

interface Environment {
    id: number;
    name: string;
    slug: string;
    type: string;
    color: string | null;
}

const ENV_COLORS = [
    { label: 'Green', value: 'green', class: 'bg-emerald-500' },
    { label: 'Amber', value: 'amber', class: 'bg-amber-500' },
    { label: 'Blue', value: 'blue', class: 'bg-blue-500' },
    { label: 'Purple', value: 'purple', class: 'bg-violet-500' },
    { label: 'Red', value: 'red', class: 'bg-rose-500' },
    { label: 'Gray', value: 'gray', class: 'bg-zinc-400' },
];

const ENV_TYPE_LABELS: Record<string, string> = {
    production: 'Production',
    staging: 'Staging',
    development: 'Development',
    custom: 'Custom',
};

function EnvironmentRow({
    environment,
    organization,
    project,
}: {
    environment: Environment;
    organization: Organization;
    project: Project;
}) {
    const form = useForm({
        name: environment.name,
        color: environment.color ?? 'gray',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            `/settings/organizations/${organization.slug}/applications/${project.slug}/environments/${environment.id}`,
            { onSuccess: () => toast.success('Environment updated') },
        );
    }

    const isDirty = form.data.name !== environment.name || form.data.color !== (environment.color ?? 'gray');

    return (
        <form onSubmit={handleSubmit} className="flex items-center gap-4 px-4 py-3">
            <div className="flex min-w-0 flex-1 items-center gap-3">
                <div className="flex items-center gap-1.5">
                    {ENV_COLORS.map((c) => (
                        <button
                            key={c.value}
                            type="button"
                            onClick={() => form.setData('color', c.value)}
                            title={c.label}
                            className={cn(
                                'size-4 rounded-full transition-all',
                                c.class,
                                form.data.color === c.value
                                    ? 'ring-2 ring-offset-2 ring-offset-background ring-current scale-110'
                                    : 'opacity-50 hover:opacity-80',
                            )}
                        />
                    ))}
                </div>

                <Input
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    className="h-8 w-40 text-sm"
                    required
                />
                <InputError message={form.errors.name} />
            </div>

            <div className="flex shrink-0 items-center gap-3">
                <Badge variant="secondary" className="capitalize">
                    {ENV_TYPE_LABELS[environment.type] ?? environment.type}
                </Badge>
                <Button
                    type="submit"
                    size="sm"
                    variant="outline"
                    disabled={form.processing || !isDirty}
                >
                    Save
                </Button>
            </div>
        </form>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '#' },
];

export default function ApplicationEdit({
    organization,
    project,
    environments,
}: {
    organization: Organization;
    project: Project;
    environments: Environment[];
}) {
    const form = useForm({
        name: project.name,
        description: project.description ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            `/settings/organizations/${organization.slug}/applications/${project.slug}`,
            { onSuccess: () => toast.success('Application updated') },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${project.name} — Application settings`} />

            <h1 className="sr-only">Application Settings</h1>

            <SettingsLayout>
                <div className="space-y-8">
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title={project.name}
                            description="Manage application name, description, and environments"
                        />

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="app-name">Name</Label>
                                <Input
                                    id="app-name"
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="app-description">
                                    Description{' '}
                                    <span className="text-muted-foreground font-normal">(optional)</span>
                                </Label>
                                <Textarea
                                    id="app-description"
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    placeholder="What does this application do?"
                                />
                                <InputError message={form.errors.description} />
                            </div>

                            <Button disabled={form.processing}>Save</Button>
                        </form>
                    </div>

                    {environments.length > 0 && (
                        <div className="space-y-3">
                            <div>
                                <h3 className="text-sm font-medium">Environments</h3>
                                <p className="text-sm text-muted-foreground">
                                    Update the name and color of each environment.
                                </p>
                            </div>

                            <div className="divide-y divide-border rounded-lg border">
                                {environments.map((env) => (
                                    <EnvironmentRow
                                        key={env.id}
                                        environment={env}
                                        organization={organization}
                                        project={project}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
