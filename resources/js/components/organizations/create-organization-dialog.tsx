import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

function xsrfToken(): string {
    return decodeURIComponent(
        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
    );
}

export function CreateOrganizationDialog({ open, onOpenChange }: Props) {
    const [name, setName] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function handleOpenChange(value: boolean) {
        if (!value) {
            setName('');
            setError(null);
        }
        onOpenChange(value);
    }

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const res = await fetch('/organizations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({ name }),
            });

            const data = await res.json();

            if (!res.ok) {
                setError(
                    data.errors?.name?.[0] ??
                        data.errors?.slug?.[0] ??
                        'An error occurred.',
                );
                return;
            }

            handleOpenChange(false);
            router.visit(
                `/settings/organizations/${data.organization.slug}/general`,
                {
                    onFinish: () =>
                        router.reload({
                            only: [
                                'organizations',
                                'activeOrganization',
                                'projectGroups',
                            ],
                        }),
                },
            );
        } catch {
            setError('An unexpected error occurred.');
        } finally {
            setLoading(false);
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-sm">
                <DialogHeader>
                    <DialogTitle>New Organization</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="org-name">Name</Label>
                        <Input
                            id="org-name"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder="Acme Corp"
                            autoFocus
                            required
                        />
                        {error && (
                            <p className="text-sm text-destructive">{error}</p>
                        )}
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
                            disabled={loading || !name.trim()}
                        >
                            {loading && (
                                <Loader2 className="mr-2 size-4 animate-spin" />
                            )}
                            Create
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
