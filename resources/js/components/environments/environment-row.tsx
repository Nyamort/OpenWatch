import { useForm } from '@inertiajs/react';
import { MoreHorizontal, RefreshCw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { ColorPicker } from '@/components/ui/color-picker';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';

interface Organization {
    slug: string;
}

interface Project {
    slug: string;
}

export interface Environment {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    url: string | null;
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

export function EnvironmentRow({
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

            <form onSubmit={handleSubmit} className="space-y-2 px-4 py-3">
                <div className="flex items-center gap-2">
                    <ColorPicker value={form.data.color} onChange={(v) => form.setData('color', v)} />
                    <Input
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        className="h-8 flex-1 text-sm"
                        required
                    />
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button type="button" size="icon" variant="ghost" className="size-8 shrink-0">
                                <MoreHorizontal className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => setRotateOpen(true)}>
                                <RefreshCw className="mr-2 size-3.5" />
                                Rotate token
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                onClick={() => setDeleteOpen(true)}
                                className="text-red-500 focus:bg-red-500/10 focus:text-red-500 dark:text-red-400 dark:focus:text-red-400"
                            >
                                <Trash2 className="mr-2 size-3.5" />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div className="flex items-center gap-2 pl-6">
                    <Input
                        type="url"
                        value={form.data.url}
                        onChange={(e) => form.setData('url', e.target.value)}
                        placeholder="https://example.com"
                        className="h-8 flex-1 text-sm"
                    />
                    <Button type="submit" size="sm" variant="outline" disabled={form.processing || !isDirty}>
                        Save
                    </Button>
                </div>
                <InputError message={form.errors.name ?? form.errors.url} />
            </form>
        </>
    );
}
