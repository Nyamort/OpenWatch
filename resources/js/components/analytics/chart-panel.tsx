import type { ReactNode } from 'react';
import { ChartContainer, type ChartConfig } from '@/components/ui/chart';

interface ChartPanelProps {
    config: ChartConfig;
    title: string;
    heroValue: ReactNode;
    legendStats: ReactNode;
    firstBucket?: string;
    lastBucket?: string;
    children: (legendContent: () => ReactNode) => ReactNode;
}

function formatBucketDatetime(bucket: string): string {
    return new Date(bucket).toLocaleString([], {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

export function ChartPanel({ config, title, heroValue, legendStats, firstBucket, lastBucket, children }: ChartPanelProps) {
    const legendContent = () => (
        <div className="mb-3 flex items-center justify-between text-sm">
            <div>
                <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">{title}</p>
                <p className="mt-1 font-bold tabular-nums">{heroValue}</p>
            </div>
            {legendStats}
        </div>
    );

    return (
        <div className="bg-card flex flex-col rounded-xl border p-5">
            <ChartContainer config={config} className="min-h-0 w-full flex-1 max-h-[270px]">
                {children(legendContent) as React.ReactElement}
            </ChartContainer>
            {firstBucket && lastBucket && (
                <div className="text-muted-foreground mt-1 flex justify-between text-xs">
                    <span>{formatBucketDatetime(firstBucket)}</span>
                    <span>{formatBucketDatetime(lastBucket)}</span>
                </div>
            )}
        </div>
    );
}
