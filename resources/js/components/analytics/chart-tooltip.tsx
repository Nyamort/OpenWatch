import type { ReactNode } from 'react';

interface TooltipRow {
    color: string;
    label: string;
    value: ReactNode;
}

interface AnalyticsTooltipProps {
    active?: boolean;
    label?: string | number;
    rows: TooltipRow[];
    footer?: ReactNode;
}

function formatTooltipDatetime(bucket: string): string {
    return new Date(bucket).toLocaleString([], {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

export function AnalyticsTooltip({
    active,
    label,
    rows,
    footer,
}: AnalyticsTooltipProps) {
    if (!active || !label) {
        return null;
    }

    return (
        <div className="min-w-72 divide-y divide-neutral-700 rounded-md border border-neutral-700 bg-neutral-900 font-mono text-xs leading-3 text-white uppercase shadow-xl">
            <div className="p-4">
                <div className="flex gap-1">
                    <span>{formatTooltipDatetime(String(label))}</span>
                    <span className="text-neutral-400">UTC</span>
                </div>
            </div>
            <div className="grid gap-4 p-4">
                {rows.map((row, i) => (
                    <div key={i} className="flex w-full items-stretch gap-2">
                        <div
                            className="w-1 shrink-0 rounded-sm"
                            style={{ backgroundColor: row.color }}
                        />
                        <div className="flex flex-1 items-center justify-between leading-none">
                            <span className="pr-3 text-neutral-300">
                                {row.label}
                            </span>
                            <span className="text-right font-medium text-neutral-200 tabular-nums">
                                {row.value}
                            </span>
                        </div>
                    </div>
                ))}
                {footer}
            </div>
        </div>
    );
}
