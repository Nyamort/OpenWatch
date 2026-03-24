import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

interface Organization {
    slug: string;
}

interface Project {
    name: string;
    slug: string;
}

export function DeleteApplicationDialog({
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
