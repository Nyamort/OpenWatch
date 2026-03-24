import { Head, Link, useForm } from '@inertiajs/react';
import { Layers, MoreHorizontal, Plus, Settings2, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { DeleteApplicationDialog } from '@/components/organizations/delete-application-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
    environments_count: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '#' },
];

const AVATAR_COLORS = [
    'bg-violet-500', 'bg-blue-500', 'bg-emerald-500',
    'bg-orange-500', 'bg-rose-500', 'bg-amber-500',
];

function avatarColor(name: string): string {
    return AVATAR_COLORS[name.charCodeAt(0) % AVATAR_COLORS.length];
}

function ProjectRow({ organization, project }: { organization: Organization; project: Project }) {
    const [deleteOpen, setDeleteOpen] = useState(false);

    return (
        <>
            <DeleteApplicationDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                organization={organization}
                project={project}
            />

            <div className="flex items-center justify-between gap-4 px-4 py-3">
                <div className="flex min-w-0 items-center gap-3">
                    <div className={`flex size-8 shrink-0 items-center justify-center rounded-md text-sm font-bold text-white ${avatarColor(project.name)}`}>
                        {project.name.charAt(0).toUpperCase()}
                    </div>
                    <div className="min-w-0 space-y-0.5">
                        <p className="truncate text-sm font-medium">{project.name}</p>
                        {project.description && (
                            <p className="truncate text-xs text-muted-foreground">{project.description}</p>
                        )}
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-3">
                    <Badge variant="secondary" className="gap-1">
                        <Layers className="size-3" />
                        {project.environments_count}
                    </Badge>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="size-8">
                                <MoreHorizontal className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link href={`/settings/organizations/${organization.slug}/applications/${project.slug}`}>
                                    <Settings2 className="mr-2 size-3.5" />
                                    Settings
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                onClick={() => setDeleteOpen(true)}
                                className="text-red-500 focus:bg-red-500/10 focus:text-red-500 dark:text-red-500 dark:focus:text-red-500"
                            >
                                <Trash2 className="mr-2 size-3.5" />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </>
    );
}

function CreateApplicationDialog({
    open,
    onOpenChange,
    organization,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    organization: Organization;
}) {
    const form = useForm({ name: '', description: '' });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(`/settings/organizations/${organization.slug}/applications`, {
            onSuccess: () => {
                onOpenChange(false);
                form.reset();
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-sm">
                <DialogHeader>
                    <DialogTitle>New application</DialogTitle>
                    <DialogDescription>
                        Create a new application within <strong>{organization.name}</strong>.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="app-name">Name</Label>
                        <Input
                            id="app-name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="My Application"
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="app-description">Description <span className="text-muted-foreground">(optional)</span></Label>
                        <Textarea
                            id="app-description"
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            placeholder="What does this application do?"
                            rows={3}
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Create
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Applications({
    organization,
    projects,
}: {
    organization: Organization;
    projects: Project[];
}) {
    const [createOpen, setCreateOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Applications" />

            <h1 className="sr-only">Applications</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Applications"
                            description="Manage your organization's applications"
                        />
                        <Button size="sm" onClick={() => setCreateOpen(true)}>
                            <Plus className="mr-1.5 size-3.5" />
                            New
                        </Button>
                    </div>

                    <CreateApplicationDialog
                        open={createOpen}
                        onOpenChange={setCreateOpen}
                        organization={organization}
                    />

                    <div className="divide-y divide-border rounded-lg border">
                        {projects.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-12 text-center">
                                <Layers className="size-8 text-muted-foreground/40" />
                                <p className="text-sm text-muted-foreground">No applications yet.</p>
                            </div>
                        ) : (
                            projects.map((project) => (
                                <ProjectRow key={project.id} organization={organization} project={project} />
                            ))
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
