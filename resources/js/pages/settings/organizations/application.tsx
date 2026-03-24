import { Head, useForm } from '@inertiajs/react';
import { ImageIcon, Plus, Trash2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { DangerZone } from '@/components/danger-zone';
import { AddEnvironmentDialog } from '@/components/environments/add-environment-dialog';
import { DeleteApplicationDialog } from '@/components/organizations/delete-application-dialog';
import { EnvironmentRow } from '@/components/environments/environment-row';
import type { Environment } from '@/components/environments/environment-row';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { TokenDialog } from '@/components/environments/token-dialog';
import { Button } from '@/components/ui/button';
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
    const [deleteAppOpen, setDeleteAppOpen] = useState(false);
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
                            <div className="grid gap-2">
                                <Label>Logo</Label>
                                <div className="flex items-center gap-4">
                                    <div
                                        className="flex size-16 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-dashed border-input bg-muted transition-colors hover:bg-accent"
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        {preview ? (
                                            <img src={preview} alt="Logo preview" className="size-full object-cover" />
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
                                        <p className="text-xs text-muted-foreground">PNG, JPG up to 2 MB</p>
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
                                    <span className="font-normal text-muted-foreground">(optional)</span>
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
                            <Button type="button" variant="outline" size="sm" onClick={() => setAddEnvOpen(true)}>
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

                    <DangerZone>
                        <Button type="button" variant="destructive" onClick={() => setDeleteAppOpen(true)}>
                            <Trash2 className="mr-1.5 size-3.5" />
                            Delete application
                        </Button>
                    </DangerZone>
                </div>
            </SettingsLayout>

            <DeleteApplicationDialog
                open={deleteAppOpen}
                onOpenChange={setDeleteAppOpen}
                organization={organization}
                project={project}
            />
        </AppLayout>
    );
}
