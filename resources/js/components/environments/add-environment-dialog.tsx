import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { ColorPicker } from '@/components/ui/color-picker';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Organization {
    slug: string;
}

interface Application {
    slug: string;
}

export function AddEnvironmentDialog({
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
            `/settings/organizations/${organization.slug}/applications/${application.slug}/environments`,
            { onSuccess: () => handleOpenChange(false) },
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
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            placeholder="Production"
                            autoFocus
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Color</Label>
                        <ColorPicker
                            value={form.data.color}
                            onChange={(v) => form.setData('color', v)}
                        />
                        <InputError message={form.errors.color} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="env-url">
                            URL{' '}
                            <span className="font-normal text-muted-foreground">
                                (optional)
                            </span>
                        </Label>
                        <Input
                            id="env-url"
                            type="url"
                            value={form.data.url}
                            onChange={(e) =>
                                form.setData('url', e.target.value)
                            }
                            placeholder="https://example.com"
                        />
                        <InputError message={form.errors.url} />
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing || !form.data.name.trim()}
                        >
                            Add Environment
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
