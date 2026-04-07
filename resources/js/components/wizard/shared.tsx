import { Check, CheckCircle2, ClipboardCopy } from 'lucide-react';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';

export function CodeBlock({
    children,
    onCopy,
}: {
    children: React.ReactNode;
    onCopy: string;
}) {
    const [copiedText, copy] = useClipboard();

    return (
        <div className="relative flex min-w-0 items-start rounded-md bg-zinc-900 px-4 py-3 font-mono text-sm">
            <span className="scrollbar-none min-w-0 flex-1 overflow-x-auto pr-2 whitespace-pre">
                {children}
            </span>
            <button
                onClick={() => copy(onCopy)}
                className="ml-1 shrink-0 text-zinc-400 transition-colors hover:text-zinc-100"
                title="Copy"
            >
                {copiedText !== null ? (
                    <Check className="size-4 text-emerald-400" />
                ) : (
                    <ClipboardCopy className="size-4" />
                )}
            </button>
        </div>
    );
}

export function EnvVar({ name, value }: { name: string; value: string }) {
    return (
        <span>
            <span className="text-emerald-400">{name}</span>
            <span className="text-zinc-100">={value}</span>
        </span>
    );
}

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
