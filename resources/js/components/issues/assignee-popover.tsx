import { router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { update } from '@/routes/issues';

interface Member {
    id: number;
    name: string;
    email: string;
}

interface AssigneePopoverProps {
    environmentSlug: string;
    issueId: number;
    assignee: Member | null;
    members: Member[];
    only?: string[];
}

export function AssigneePopover({
    environmentSlug,
    issueId,
    assignee,
    members,
    only = ['issues'],
}: AssigneePopoverProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');

    const filtered = members.filter(
        (m) =>
            m.name.toLowerCase().includes(search.toLowerCase()) ||
            m.email.toLowerCase().includes(search.toLowerCase()),
    );

    function handleSelect(userId: number | null) {
        setOpen(false);
        setSearch('');
        router.patch(
            update.url({ environment: environmentSlug, issue: issueId }),
            { assignee_id: userId },
            { preserveScroll: true, only },
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                onClick={(e) => e.stopPropagation()}
                className="cursor-pointer rounded-full focus:outline-none"
            >
                {assignee ? (
                    <div
                        className="flex size-6 items-center justify-center rounded-full bg-muted text-xs font-medium"
                        title={assignee.name}
                    >
                        {assignee.name[0].toUpperCase()}
                    </div>
                ) : (
                    <div className="size-6 rounded-full border border-dashed border-muted-foreground/40 hover:border-muted-foreground/70" />
                )}
            </PopoverTrigger>
            <PopoverContent
                className="w-56 p-0"
                align="start"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="border-b px-3 py-2">
                    <input
                        autoFocus
                        placeholder="Search members..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    />
                </div>
                <div className="max-h-48 overflow-y-auto p-1">
                    {assignee && (
                        <button
                            onClick={() => handleSelect(null)}
                            className="flex w-full items-center gap-2 rounded px-2 py-1.5 text-sm text-muted-foreground hover:bg-muted"
                        >
                            <div className="size-5 rounded-full border border-dashed border-muted-foreground/40" />
                            <span className="flex-1 text-left">Unassign</span>
                        </button>
                    )}
                    {filtered.length === 0 ? (
                        <p className="px-2 py-3 text-center text-xs text-muted-foreground">
                            No members found.
                        </p>
                    ) : (
                        filtered.map((member) => (
                            <button
                                key={member.id}
                                onClick={() => handleSelect(member.id)}
                                className="flex w-full items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-muted"
                            >
                                <div className="flex size-5 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                    {member.name[0].toUpperCase()}
                                </div>
                                <span className="flex-1 truncate text-left">
                                    {member.name}
                                </span>
                                {assignee?.id === member.id && (
                                    <Check className="size-3.5 text-muted-foreground" />
                                )}
                            </button>
                        ))
                    )}
                </div>
            </PopoverContent>
        </Popover>
    );
}
