import { type ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function InfoRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-baseline gap-2 py-1 text-sm first:pt-0 last:pb-0">
            <span className="shrink-0 uppercase text-muted-foreground">{label}</span>
            <span className="relative -bottom-px grow border-b-2 border-dotted border-neutral-300 dark:border-white/20" />
            <span className="shrink-0 text-right font-medium">{value ?? '—'}</span>
        </div>
    );
}

export function Section({
    title,
    children,
    className,
}: {
    title?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('flex flex-col gap-1', className)}>
            {title && (
                <h3 className="mb-1 text-base font-semibold text-foreground">{title}</h3>
            )}
            {children}
        </div>
    );
}
