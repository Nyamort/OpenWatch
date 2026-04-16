import { cn } from '@/lib/utils';

export function SourceLabel({
    source,
    className,
}: {
    source: string;
    className?: string;
}) {
    const parenIndex = source.indexOf('(');
    const funcPart = parenIndex === -1 ? source : source.slice(0, parenIndex);
    const args = parenIndex === -1 ? '' : source.slice(parenIndex + 1, -1);

    return (
        <span
            className={cn('truncate text-muted-foreground', className)}
            title={source}
        >
            <span className="font-mono">
                <span className="text-violet-500 dark:text-violet-400">
                    {funcPart}
                </span>
                <span className="text-orange-400 dark:text-orange-300">
                    <span className="opacity-50">(</span>
                    {args}
                    <span className="opacity-50">)</span>
                </span>
            </span>
        </span>
    );
}
