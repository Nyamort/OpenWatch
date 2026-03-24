import { Head, useForm } from '@inertiajs/react';
import { Check, Copy, ImageIcon, Plus, RefreshCw, Trash2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ColorPicker } from '@/components/ui/color-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
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
    color: string | null;
    url: string | null;
}

function TokenDialog({
    open,
    onOpenChange,
    token,
    environmentName,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    token: string;
    environmentName: string;
}) {
    const [copied, setCopied] = useState(false);

    function copyToken() {
        navigator.clipboard.writeText(token);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Ingest token — {environmentName}</DialogTitle>
                    <DialogDescription>
                        Copy this token now. It will not be shown again.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex items-center gap-2 rounded-md border bg-muted px-3 py-2">
                    <code className="flex-1 truncate text-sm font-mono">{token}</code>
                    <Button type="button" size="icon" variant="ghost" className="size-7 shrink-0" onClick={copyToken}>
                        {copied ? <Check className="size-3.5 text-green-500" /> : <Copy className="size-3.5" />}
                    </Button>
                </div>

                <DialogFooter>
                    <Button onClick={() => onOpenChange(false)}>Done</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function RotateTokenDialog({
    open,
    onOpenChange,
    organization,
    project,
    environment,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    organization: Organization;
    project: Project;
    environment: Environment;
}) {
    const form = useForm({});

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(
            `/settings/organizations/${organization.slug}/applications/${project.slug}/environments/${environment.slug}/rotate-token`,
            { onSuccess: () => onOpenChange(false) },
        );
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-sm">
                <DialogHeader>
                    <DialogTitle>Rotate token — {environment.name}</DialogTitle>
                    <DialogDescription>
                        The current token will enter a 3-day grace period before being revoked. A new token will be generated and shown once.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="destructive" disabled={form.processing}>
                            Rotate token
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteEnvironmentDialog({
    open,
    onOpenChange,
    organization,
    project,
    environment,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    organization: Organization;
    project: Project;
    environment: Environment;
}) {
    const form = useForm({});

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.delete(
            `/settings/organizations/${organization.slug}/applications/${project.slug}/environments/${environment.slug}`,
            { onSuccess: () => onOpenChange(false) },
        );
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-sm">
                <DialogHeader>
                    <DialogTitle>Delete environment</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete <strong>{environment.name}</strong>? This will permanently remove all associated tokens and data.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="destructive" disabled={form.processing}>
                            Delete
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

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
        color: 'green',
        url: '',
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
                onSuccess: () => handleOpenChange(false),
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
                        <Label>Color</Label>
                        <ColorPicker value={form.data.color} onChange={(v) => form.setData('color', v)} />
                        <InputError message={form.errors.color} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="env-url">
                            URL <span className="text-muted-foreground font-normal">(optional)</span>
                        </Label>
                        <Input
                            id="env-url"
                            type="url"
                            value={form.data.url}
                            onChange={(e) => form.setData('url', e.target.value)}
                            placeholder="https://example.com"
                        />
                        <InputError message={form.errors.url} />
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
    const [rotateOpen, setRotateOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const form = useForm({
        name: environment.name,
        color: environment.color ?? 'gray',
        url: environment.url ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            `/settings/organizations/${organization.slug}/applications/${project.slug}/environments/${environment.slug}`,
            { onSuccess: () => toast.success('Environment updated') },
        );
    }

    const isDirty =
        form.data.name !== environment.name ||
        form.data.color !== (environment.color ?? 'gray') ||
        form.data.url !== (environment.url ?? '');

    return (
        <>
            <RotateTokenDialog
                open={rotateOpen}
                onOpenChange={setRotateOpen}
                organization={organization}
                project={project}
                environment={environment}
            />
            <DeleteEnvironmentDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                organization={organization}
                project={project}
                environment={environment}
            />

            <form onSubmit={handleSubmit} className="flex items-center gap-4 px-4 py-3">
                <div className="flex min-w-0 flex-1 items-center gap-3">
                    <ColorPicker value={form.data.color} onChange={(v) => form.setData('color', v)} />

                    <Input
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        className="h-8 w-36 text-sm"
                        required
                    />
                    <Input
                        type="url"
                        value={form.data.url}
                        onChange={(e) => form.setData('url', e.target.value)}
                        placeholder="https://example.com"
                        className="h-8 w-52 text-sm"
                    />
                    <InputError message={form.errors.name ?? form.errors.url} />
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => setRotateOpen(true)}
                        title="Rotate ingest token"
                    >
                        <RefreshCw className="size-3.5" />
                        Rotate token
                    </Button>
                    <Button
                        type="submit"
                        size="sm"
                        variant="outline"
                        disabled={form.processing || !isDirty}
                    >
                        Save
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={() => setDeleteOpen(true)}
                        title="Delete environment"
                        className="text-destructive hover:text-destructive"
                    >
                        <Trash2 className="size-3.5" />
                    </Button>
                </div>
            </form>
        </>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '#' },
];

export default function ApplicationEdit({
    organization,
    project,
    environments,
    newToken,
    newTokenEnvironmentName,
}: {
    organization: Organization;
    project: Project;
    environments: Environment[];
    newToken: string | null;
    newTokenEnvironmentName: string | null;
}) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(project.logo_url || null);
    const [addEnvOpen, setAddEnvOpen] = useState(false);
    const [tokenDialogOpen, setTokenDialogOpen] = useState(false);
    const [displayedToken, setDisplayedToken] = useState<string | null>(null);
    const [displayedTokenEnvName, setDisplayedTokenEnvName] = useState<string>('');

    useEffect(() => {
        if (newToken) {
            setDisplayedToken(newToken);
            setDisplayedTokenEnvName(newTokenEnvironmentName ?? '');
            setTokenDialogOpen(true);
        }
    }, [newToken]);

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

            {displayedToken && (
                <TokenDialog
                    open={tokenDialogOpen}
                    onOpenChange={setTokenDialogOpen}
                    token={displayedToken}
                    environmentName={displayedTokenEnvName}
                />
            )}

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
