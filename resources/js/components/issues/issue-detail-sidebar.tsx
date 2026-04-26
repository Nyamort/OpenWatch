import { router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { Check, Circle, CircleCheck, CircleMinus } from 'lucide-react';
import { useState } from 'react';
import { AssigneePopover } from '@/components/issues/assignee-popover';
import { PriorityBars } from '@/components/issues/priority-bars';
import { PriorityPopover } from '@/components/issues/priority-popover';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { update } from '@/routes/issues';

const STATUSES = [
    { value: 'open', label: 'Open' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'ignored', label: 'Ignored' },
] as const;

const PRIORITY_LABELS: Record<string, string> = {
    none: 'No priority',
    low: 'Low',
    medium: 'Medium',
    high: 'High',
};

const ONLY = ['issue', 'timeline'];

function StatusIcon({ status }: { status: string }) {
    if (status === 'resolved') {
        return <CircleCheck className="size-3.5 text-blue-500" />;
    }
    if (status === 'ignored') {
        return <CircleMinus className="size-3.5 text-muted-foreground" />;
    }
    return <Circle className="size-3.5 fill-green-500 text-green-500" />;
}

function StatusPopover({
    environmentSlug,
    issueId,
    status,
}: {
    environmentSlug: string;
    issueId: number;
    status: string;
}) {
    const [open, setOpen] = useState(false);
    const currentLabel = STATUSES.find((s) => s.value === status)?.label ?? status;

    function handleSelect(value: string) {
        setOpen(false);
        router.patch(
            update.url({ environment: environmentSlug, issue: issueId }),
            { status: value },
            { preserveScroll: true, only: ONLY },
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger className="flex cursor-pointer items-center gap-1.5 rounded px-1.5 py-0.5 text-sm hover:bg-muted">
                <StatusIcon status={status} />
                <span>{currentLabel}</span>
            </PopoverTrigger>
            <PopoverContent className="w-36 p-1" align="start">
                {STATUSES.map((s) => (
                    <button
                        key={s.value}
                        onClick={() => handleSelect(s.value)}
                        className="flex w-full items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-muted"
                    >
                        <StatusIcon status={s.value} />
                        <span className="flex-1 text-left">{s.label}</span>
                        {status === s.value && <Check className="size-3.5 text-muted-foreground" />}
                    </button>
                ))}
            </PopoverContent>
        </Popover>
    );
}

interface Member {
    id: number;
    name: string;
    email: string;
}

interface IssueDetailSidebarProps {
    environmentSlug: string;
    issue: {
        id: number;
        status: string;
        priority: string;
        assignee: Member | null;
        first_seen_at: string;
        last_seen_at: string;
    };
    members: Member[];
}

function SidebarRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-2">
            <span className="text-sm text-muted-foreground">{label}</span>
            <div className="flex items-center">{children}</div>
        </div>
    );
}

export function IssueDetailSidebar({ environmentSlug, issue, members }: IssueDetailSidebarProps) {
    return (
        <Card className="py-0">
            <CardHeader className="border-b px-4 py-3">
                <CardTitle className="text-sm font-medium">Details</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-3 px-4 pb-4">
                <SidebarRow label="Status">
                    <StatusPopover
                        environmentSlug={environmentSlug}
                        issueId={issue.id}
                        status={issue.status}
                    />
                </SidebarRow>

                <SidebarRow label="Priority">
                    <div className="flex items-center gap-1.5 px-1.5">
                        <PriorityPopover
                            environmentSlug={environmentSlug}
                            issueId={issue.id}
                            priority={issue.priority}
                            only={ONLY}
                        />
                        <span className="text-sm">{PRIORITY_LABELS[issue.priority] ?? issue.priority}</span>
                    </div>
                </SidebarRow>

                <SidebarRow label="Assignee">
                    <div className="flex items-center gap-1.5 px-1.5">
                        <AssigneePopover
                            environmentSlug={environmentSlug}
                            issueId={issue.id}
                            assignee={issue.assignee}
                            members={members}
                            only={ONLY}
                        />
                        <span className="text-sm">
                            {issue.assignee ? issue.assignee.name : <span className="text-muted-foreground">Unassigned</span>}
                        </span>
                    </div>
                </SidebarRow>

                <div className="my-1 border-t" />

                <SidebarRow label="First seen">
                    <span className="text-sm" title={issue.first_seen_at}>
                        {format(parseISO(issue.first_seen_at), 'MMM d, yyyy')}
                    </span>
                </SidebarRow>

                <SidebarRow label="Last seen">
                    <span className="text-sm" title={issue.last_seen_at}>
                        {format(parseISO(issue.last_seen_at), 'MMM d, yyyy')}
                    </span>
                </SidebarRow>
            </CardContent>
        </Card>
    );
}
