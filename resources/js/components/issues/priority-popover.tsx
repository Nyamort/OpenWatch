import { router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';
import { PriorityBars } from '@/components/issues/priority-bars';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { update } from '@/routes/issues';

const priorities = [
    { value: 'none', label: 'No priority' },
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
] as const;

interface PriorityPopoverProps {
    environmentSlug: string;
    issueId: number;
    priority: string;
}

export function PriorityPopover({
    environmentSlug,
    issueId,
    priority,
}: PriorityPopoverProps) {
    const [open, setOpen] = useState(false);

    function handleSelect(value: string) {
        setOpen(false);
        router.patch(
            update.url({ environment: environmentSlug, issue: issueId }),
            { priority: value },
            { preserveScroll: true, only: ['issues'] },
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                onClick={(e) => e.stopPropagation()}
                className="cursor-pointer rounded p-1 hover:bg-muted"
            >
                <PriorityBars priority={priority} />
            </PopoverTrigger>
            <PopoverContent
                className="w-44 p-1"
                align="start"
                onClick={(e) => e.stopPropagation()}
            >
                {priorities.map((option) => (
                    <button
                        key={option.value}
                        onClick={() => handleSelect(option.value)}
                        className="flex w-full items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-muted"
                    >
                        <PriorityBars priority={option.value} />
                        <span className="flex-1 text-left">{option.label}</span>
                        {priority === option.value && (
                            <Check className="size-3.5 text-muted-foreground" />
                        )}
                    </button>
                ))}
            </PopoverContent>
        </Popover>
    );
}
