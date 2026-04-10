import { cn } from '@/lib/utils';

const colors = ['bg-blue-500', 'bg-amber-500', 'bg-rose-500'];
const heights = ['h-[6px]', 'h-[9px]', 'h-[12px]'];

const priorityLevel: Record<string, number> = {
    low: 1,
    medium: 2,
    high: 3,
    critical: 3,
};

interface PriorityBarsProps {
    priority: string | number;
}

export function PriorityBars({ priority }: PriorityBarsProps) {
    const level =
        typeof priority === 'number'
            ? priority
            : (priorityLevel[priority] ?? 1);

    const activeColor = colors[level - 1];

    return (
        <div className="flex items-end gap-[2px]">
            {heights.map((height, index) => (
                <div
                    key={index}
                    className={cn(
                        height,
                        'w-[3px] rounded-full',
                        level >= index + 1
                            ? activeColor
                            : 'bg-neutral-300 dark:bg-neutral-700',
                    )}
                />
            ))}
        </div>
    );
}
