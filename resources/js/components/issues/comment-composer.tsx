import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';

interface Props {
    submitUrl: string;
}

export function CommentComposer({ submitUrl }: Props) {
    const form = useForm({ body: '' });

    function handleSubmit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.post(submitUrl, {
            onSuccess: () => form.reset('body'),
            preserveScroll: true,
        });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-2">
            <textarea
                className="min-h-[100px] w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                placeholder="Write a comment..."
                value={form.data.body}
                onChange={(e) => form.setData('body', e.target.value)}
                disabled={form.processing}
            />
            {form.errors.body && (
                <p className="text-sm text-destructive">{form.errors.body}</p>
            )}
            <div className="flex justify-end">
                <Button
                    type="submit"
                    size="sm"
                    disabled={form.processing || !form.data.body.trim()}
                >
                    {form.processing ? 'Posting...' : 'Post Comment'}
                </Button>
            </div>
        </form>
    );
}
