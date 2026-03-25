import { useRef, useState } from 'react';
import type { ReactNode, MouseEvent } from 'react';
import { ChartContainer, type ChartConfig } from '@/components/ui/chart';

interface TooltipPosition {
    x: number;
    y: number;
}

interface ChartPanelProps {
    config: ChartConfig;
    title: string;
    heroValue: ReactNode;
    legendStats: ReactNode;
    firstBucket?: string;
    lastBucket?: string;
    children: (legendContent: () => ReactNode, tooltipPos: TooltipPosition | undefined) => ReactNode;
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

const TOOLTIP_WIDTH = 288; // min-w-72
const OFFSET = 16;

export function ChartPanel({ config, title, heroValue, legendStats, firstBucket, lastBucket, children }: ChartPanelProps) {
    const [tooltipPos, setTooltipPos] = useState<TooltipPosition | undefined>(undefined);
    const wrapperRef = useRef<HTMLDivElement>(null);

    const handleMouseMove = (e: MouseEvent<HTMLDivElement>) => {
        if (!wrapperRef.current) return;
        const rect = wrapperRef.current.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const goLeft = x + TOOLTIP_WIDTH + OFFSET > rect.width;
        setTooltipPos({
            x: goLeft ? x - TOOLTIP_WIDTH - OFFSET : x + OFFSET,
            y: y + OFFSET,
        });
    };

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
        <div className="bg-surface flex flex-col rounded-xl border p-5">
            <div ref={wrapperRef} onMouseMove={handleMouseMove} onMouseLeave={() => setTooltipPos(undefined)}>
                <ChartContainer config={config} className="min-h-0 w-full flex-1 max-h-[270px]">
                    {children(legendContent, tooltipPos) as React.ReactElement}
                </ChartContainer>
            </div>
            {firstBucket && lastBucket && (
                <div className="text-muted-foreground mt-1 flex justify-between text-xs">
                    <span>{formatBucketDatetime(firstBucket)}</span>
                    <span>{formatBucketDatetime(lastBucket)}</span>
                </div>
            )}
        </div>
    );
}
