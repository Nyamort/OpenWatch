import { useState } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const ENV_COLORS = [
    { label: 'Green', value: 'green', class: 'bg-emerald-500' },
    { label: 'Amber', value: 'amber', class: 'bg-amber-500' },
    { label: 'Blue', value: 'blue', class: 'bg-blue-500' },
    { label: 'Purple', value: 'purple', class: 'bg-violet-500' },
    { label: 'Red', value: 'red', class: 'bg-rose-500' },
    { label: 'Gray', value: 'gray', class: 'bg-zinc-400' },
];

export { ENV_COLORS };

export function ColorPicker({ value, onChange }: { value: string; onChange: (v: string) => void }) {
    const [open, setOpen] = useState(false);
    const current = ENV_COLORS.find((c) => c.value === value) ?? ENV_COLORS[0];

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    title={current.label}
                    className="size-4 shrink-0 rounded-full ring-offset-background transition-all hover:scale-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <span className={cn('block size-full rounded-full', current.class)} />
                </button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-2" align="start">
                <div className="flex gap-1.5">
                    {ENV_COLORS.map((c) => (
                        <button
                            key={c.value}
                            type="button"
                            title={c.label}
                            onClick={() => { onChange(c.value); setOpen(false); }}
                            className={cn(
                                'size-5 rounded-full transition-all hover:scale-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 ring-offset-background',
                                c.class,
                                value === c.value && 'ring-2 ring-offset-2 ring-current scale-110',
                            )}
                        />
                    ))}
                </div>
            </PopoverContent>
        </Popover>
    );
}
