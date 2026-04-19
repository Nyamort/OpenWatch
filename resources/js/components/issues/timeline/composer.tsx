import { router, useForm } from '@inertiajs/react';
import { marked } from 'marked';
import type { FormEvent } from 'react';
import { useState } from 'react';
import IssueCommentController from '@/actions/App/Http/Controllers/Issues/IssueCommentController';
import IssueController from '@/actions/App/Http/Controllers/Issues/IssueController';
import { UserAvatar } from '@/components/issues/timeline/user-avatar';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { TimelineUser } from '@/types/timeline';

interface Props {
    environmentSlug: string;
    issueId: number;
    currentUser: TimelineUser | null;
    issueStatus: 'open' | 'resolved' | 'ignored';
    canComment: boolean;
    canChangeStatus: boolean;
}

type Tab = 'write' | 'preview';

export function TimelineComposer({
    environmentSlug,
    issueId,
    currentUser,
    issueStatus,
    canComment,
    canChangeStatus,
}: Props) {
    const [tab, setTab] = useState<Tab>('write');
    const form = useForm({ body: '' });

    function handleSubmit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        const action = IssueCommentController.store({
            environment: environmentSlug,
            issue: issueId,
        });
        form.post(action.url, {
            onSuccess: () => {
                form.reset('body');
                setTab('write');
            },
            preserveScroll: true,
        });
    }

    function changeStatus(newStatus: 'open' | 'resolved' | 'ignored') {
        const action = IssueController.update({
            environment: environmentSlug,
            issue: issueId,
        });
        router.patch(action.url, { status: newStatus }, { preserveScroll: true });
    }

    const statusLabel = issueStatus === 'open' ? 'Resolve now' : 'Reopen';
    const nextStatus = issueStatus === 'open' ? 'resolved' : 'open';

    if (!canComment) {
        return (
            <p className="rounded-md border border-dashed bg-muted/20 p-4 text-sm text-muted-foreground">
                You don't have permission to comment on this issue.
            </p>
        );
    }

    return (
        <div className="flex items-start gap-3">
            <UserAvatar user={currentUser} size="sm" />
            <form
                onSubmit={handleSubmit}
                className="flex-1 overflow-hidden rounded-lg border bg-card"
            >
                <div className="flex items-center gap-1 border-b px-2 pt-2">
                    {(['write', 'preview'] as Tab[]).map((t) => (
                        <button
                            type="button"
                            key={t}
                            onClick={() => setTab(t)}
                            className={cn(
                                '-mb-px rounded-t-md px-3 py-1.5 text-sm font-medium capitalize transition-colors',
                                tab === t
                                    ? 'border-b-2 border-foreground text-foreground'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {t}
                        </button>
                    ))}
                </div>
                {tab === 'write' ? (
                    <textarea
                        name="body"
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                        disabled={form.processing}
                        placeholder="Add a comment..."
                        className="min-h-[100px] w-full resize-y bg-transparent px-4 py-3 text-sm placeholder:text-muted-foreground focus:outline-none"
                    />
                ) : (
                    <div
                        className="prose prose-sm min-h-[100px] max-w-none px-4 py-3 dark:prose-invert"
                        dangerouslySetInnerHTML={{
                            __html: marked(
                                form.data.body || '_Nothing to preview._',
                            ) as string,
                        }}
                    />
                )}
                {form.errors.body && (
                    <p className="px-4 pb-2 text-sm text-destructive">
                        {form.errors.body}
                    </p>
                )}
                <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-4 py-2">
                    {canChangeStatus && (
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => changeStatus(nextStatus)}
                            disabled={form.processing}
                        >
                            {statusLabel}
                        </Button>
                    )}
                    <Button
                        type="submit"
                        size="sm"
                        disabled={form.processing || !form.data.body.trim()}
                    >
                        {form.processing ? 'Posting...' : 'Comment'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
