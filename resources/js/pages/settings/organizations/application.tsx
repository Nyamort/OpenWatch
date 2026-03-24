import { Head, useForm } from '@inertiajs/react';
import { ImageIcon, Plus, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    logo_url: string;
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

const ENV_TYPES = [
    { value: 'production', label: 'Production' },
    { value: 'staging', label: 'Staging' },
    { value: 'development', label: 'Development' },
    { value: 'custom', label: 'Custom' },
];

const ENV_TYPE_LABELS: Record<string, string> = {
    production: 'Production',
    staging: 'Staging',
    development: 'Development',
    custom: 'Custom',
};

function AddEnvironmentDialog({
    open,
    onOpenChange,
    organization,
    project,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    organization: Organization;
    project: Project;
}) {
    const form = useForm({
        name: '',
        type: 'production',
        color: 'green',
    });

    function handleOpenChange(value: boolean) {
        if (!value) {
            form.reset();
        }
        onOpenChange(value);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(
            `/settings/organizations/${organization.slug}/applications/${project.slug}/environments`,
            {
                onSuccess: () => {
                    toast.success('Environment created');
                    handleOpenChange(false);
                },
            },
        );
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-sm">
                <DialogHeader>
                    <DialogTitle>Add Environment</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="env-name">Name</Label>
                        <Input
                            id="env-name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="Production"
                            autoFocus
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Type</Label>
                        <Select value={form.data.type} onValueChange={(v) => form.setData('type', v)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {ENV_TYPES.map((t) => (
                                    <SelectItem key={t.value} value={t.value}>
                                        {t.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.type} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Color</Label>
                        <div className="flex items-center gap-2">
                            {ENV_COLORS.map((c) => (
                                <button
                                    key={c.value}
                                    type="button"
                                    onClick={() => form.setData('color', c.value)}
                                    title={c.label}
                                    className={cn(
                                        'size-5 rounded-full transition-all',
                                        c.class,
                                        form.data.color === c.value
                                            ? 'ring-2 ring-offset-2 ring-offset-background ring-current scale-110'
                                            : 'opacity-50 hover:opacity-80',
                                    )}
                                />
                            ))}
                        </div>
                        <InputError message={form.errors.color} />
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={() => handleOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing || !form.data.name.trim()}>
                            Add Environment
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

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
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(project.logo_url || null);
    const [addEnvOpen, setAddEnvOpen] = useState(false);

    const form = useForm<{
        name: string;
        description: string;
        logo: File | null;
        remove_logo: boolean;
    }>({
        name: project.name,
        description: project.description ?? '',
        logo: null,
        remove_logo: false,
    });

    function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0] ?? null;
        form.setData('logo', file);
        form.setData('remove_logo', false);
        if (file) {
            setPreview(URL.createObjectURL(file));
        }
    }

    function removeLogo() {
        form.setData('logo', null);
        form.setData('remove_logo', true);
        setPreview(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            `/settings/organizations/${organization.slug}/applications/${project.slug}`,
            {
                forceFormData: true,
                onSuccess: () => toast.success('Application updated'),
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${project.name} — Application settings`} />

            <h1 className="sr-only">Application Settings</h1>

            <AddEnvironmentDialog
                open={addEnvOpen}
                onOpenChange={setAddEnvOpen}
                organization={organization}
                project={project}
            />

            <SettingsLayout>
                <div className="space-y-8">
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title={project.name}
                            description="Manage application name, description, and environments"
                        />

                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* Logo */}
                            <div className="grid gap-2">
                                <Label>Logo</Label>
                                <div className="flex items-center gap-4">
                                    <div
                                        className="flex size-16 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-dashed border-input bg-muted transition-colors hover:bg-accent"
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        {preview ? (
                                            <img
                                                src={preview}
                                                alt="Logo preview"
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <ImageIcon className="size-6 text-muted-foreground" />
                                        )}
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => fileInputRef.current?.click()}
                                        >
                                            {preview ? 'Change logo' : 'Upload logo'}
                                        </Button>
                                        {preview && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="text-muted-foreground"
                                                onClick={removeLogo}
                                            >
                                                <X className="mr-1 size-3.5" />
                                                Remove
                                            </Button>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            PNG, JPG up to 2 MB
                                        </p>
                                    </div>
                                </div>

                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/*"
                                    className="hidden"
                                    onChange={handleFileChange}
                                />
                                <InputError message={form.errors.logo} />
                            </div>

                            {/* Name */}
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

                            {/* Description */}
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

                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-medium">Environments</h3>
                                <p className="text-sm text-muted-foreground">
                                    Update the name and color of each environment.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setAddEnvOpen(true)}
                            >
                                <Plus className="mr-1.5 size-3.5" />
                                Add
                            </Button>
                        </div>

                        {environments.length > 0 ? (
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
                        ) : (
                            <p className="rounded-lg border border-dashed px-4 py-8 text-center text-sm text-muted-foreground">
                                No environments yet.
                            </p>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
