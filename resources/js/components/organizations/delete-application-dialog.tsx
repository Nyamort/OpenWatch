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

interface Application {
    name: string;
    slug: string;
}

export function DeleteApplicationDialog({
    open,
    onOpenChange,
    organization,
    application,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    organization: Organization;
    application: Application;
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
                        This will permanently delete <strong>{application.name}</strong> and all its environments and data. Type <strong>{application.name}</strong> to confirm.
                    </DialogDescription>
                </DialogHeader>
                <Input
                    value={confirm}
                    onChange={(e) => setConfirm(e.target.value)}
                    placeholder={application.name}
                />
                <DialogFooter>
                    <Button variant="outline" onClick={() => handleOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={confirm !== application.name || form.processing}
                        onClick={() => form.delete(`/settings/organizations/${organization.slug}/applications/${application.slug}`)}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
