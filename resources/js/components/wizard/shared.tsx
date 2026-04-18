import { CheckCircle2 } from 'lucide-react';
import { cn } from '@/lib/utils';

export function StepHeader({
    number,
    title,
    done,
    active,
}: {
    number: number;
    title: string;
    done: boolean;
    active: boolean;
}) {
    return (
        <div className="flex items-center gap-3">
            <div
                className={cn(
                    'flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold',
                    active
                        ? 'border-zinc-300 bg-zinc-300 text-zinc-900'
                        : 'border-zinc-600 text-zinc-500',
                )}
            >
                {number}
            </div>
            <span
                className={cn(
                    'text-sm font-medium',
                    active ? 'text-zinc-100' : 'text-zinc-500',
                )}
            >
                {title}
            </span>
            {done && (
                <span className="ml-auto flex items-center gap-1 text-xs font-semibold text-emerald-400">
                    <CheckCircle2 className="size-3.5" />
                    DONE
                </span>
            )}
        </div>
    );
}
