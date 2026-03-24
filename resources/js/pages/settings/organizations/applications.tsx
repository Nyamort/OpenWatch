import { Head, Link, useForm } from '@inertiajs/react';
import { Layers, MoreHorizontal, Settings2, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
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
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
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

function DeleteApplicationDialog({
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
    const [confirm, setConfirm] = useState('');
    const form = useForm({});

    function handleOpenChange(value: boolean) {
        if (!value) setConfirm('');
        onOpenChange(value);
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-sm">
                <DialogHeader>
                    <DialogTitle>Delete application</DialogTitle>
                    <DialogDescription>
                        This will permanently delete <strong>{project.name}</strong> and all its environments and data. Type <strong>{project.name}</strong> to confirm.
                    </DialogDescription>
                </DialogHeader>
                <Input
                    value={confirm}
                    onChange={(e) => setConfirm(e.target.value)}
                    placeholder={project.name}
                />
                <DialogFooter>
                    <Button variant="outline" onClick={() => handleOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={confirm !== project.name || form.processing}
                        onClick={() => form.delete(`/settings/organizations/${organization.slug}/applications/${project.slug}`)}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
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
                            <DropdownMenuItem
                                onClick={() => setDeleteOpen(true)}
                                className="text-destructive focus:text-destructive"
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

export default function Applications({
    organization,
    projects,
}: {
    organization: Organization;
    projects: Project[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Applications" />

            <h1 className="sr-only">Applications</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Applications"
                        description="Manage your organization's applications"
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
