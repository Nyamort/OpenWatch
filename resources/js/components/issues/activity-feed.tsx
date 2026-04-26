import { router, useForm, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/Issues/IssueCommentController';
import { update } from '@/actions/App/Http/Controllers/Issues/IssueController';
import { MarkdownEditor } from '@/components/issues/markdown-editor';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import type { Auth } from '@/types';
import { StatusIcon, TimelineEntryItem } from './activity-feed-item';
import { STATUS_LABELS, type TimelineEntry } from './activity-feed-types';

interface Props {
    timeline: TimelineEntry[];
    environment: { slug: string };
    issue: { id: number; status: string };
}

export type { TimelineEntry };

export function ActivityFeed({ timeline, environment, issue }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currentUserEmail = auth.user.email;

    const { data, setData, post, processing, reset, errors } = useForm({ body: '' });

    const [showResolveDialog, setShowResolveDialog] = useState(false);
    const [resolveComment, setResolveComment] = useState('');

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(store.url({ environment, issue: issue.id }), {
            onSuccess: () => reset('body'),
        });
    }

    function confirmResolve() {
        router.patch(
            update.url({ environment, issue: issue.id }),
            { status: 'resolved', ...(resolveComment.trim() ? { comment: resolveComment.trim() } : {}) },
            { preserveScroll: true },
        );
        setShowResolveDialog(false);
        setResolveComment('');
    }

    return (
        <>
            <Card className="gap-0 overflow-hidden bg-surface py-0">
                <CardHeader className="border-b px-5 py-3">
                    <CardTitle className="text-sm font-semibold">Activity</CardTitle>
                </CardHeader>

                <CardContent className="flex flex-col p-0">
                    <div className="px-5 py-4">
                        {timeline.length === 0 ? (
                            <p className="py-2 text-center text-sm text-muted-foreground">No activity yet.</p>
                        ) : (
                            <div className="relative flex flex-col gap-4">
                                <div className="absolute top-3 bottom-3 left-3 z-0 w-px border-l border-dashed border-neutral-300 dark:border-neutral-700/50" />
                                {timeline.map((entry) => (
                                    <TimelineEntryItem
                                        key={entry.id}
                                        entry={entry}
                                        environment={environment}
                                        issue={issue}
                                        currentUserEmail={currentUserEmail}
                                    />
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="border-t px-5 py-4">
                        <form onSubmit={submit} className="space-y-3">
                            <MarkdownEditor
                                value={data.body}
                                onChange={(value) => setData('body', value)}
                                placeholder="Leave a comment…"
                            />
                            {errors.body && <p className="text-sm text-destructive">{errors.body}</p>}
                            <div className="flex items-center justify-end gap-2">
                                {issue.status !== 'resolved' && (
                                    <button
                                        type="button"
                                        onClick={() => setShowResolveDialog(true)}
                                        className="rounded-lg border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
                                    >
                                        Resolve now
                                    </button>
                                )}
                                <button
                                    type="submit"
                                    disabled={processing || !data.body.trim()}
                                    className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity disabled:opacity-50"
                                >
                                    {processing ? 'Commenting…' : 'Comment'}
                                </button>
                            </div>
                        </form>
                    </div>
                </CardContent>
            </Card>

            <Dialog open={showResolveDialog} onOpenChange={setShowResolveDialog}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Resolve issue</DialogTitle>
                        <DialogDescription asChild>
                            <div className="flex items-center gap-2 pt-1">
                                <span className="flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium">
                                    <StatusIcon status={issue.status} />
                                    {STATUS_LABELS[issue.status] ?? issue.status}
                                </span>
                                <ArrowRight className="size-3.5 shrink-0 text-muted-foreground/60" />
                                <span className="flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium">
                                    <StatusIcon status="resolved" />
                                    Resolved
                                </span>
                            </div>
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-1.5">
                        <Textarea
                            autoFocus
                            placeholder="Describe what fixed this issue…"
                            value={resolveComment}
                            onChange={(e) => setResolveComment(e.target.value)}
                            onKeyDown={(e) => {
                                if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                                    e.preventDefault();
                                    confirmResolve();
                                }
                            }}
                            rows={4}
                            className="resize-none"
                        />
                        <p className="select-none text-right text-xs text-muted-foreground/60">
                            <kbd className="font-sans">⌘</kbd> + <kbd className="font-sans">↵</kbd> to confirm
                        </p>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowResolveDialog(false)}>
                            Cancel
                        </Button>
                        <Button onClick={confirmResolve} className="bg-green-600 text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600">
                            Resolve issue
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
