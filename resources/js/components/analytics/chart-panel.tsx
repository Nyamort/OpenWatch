import { useRef } from 'react';
import type { ReactNode } from 'react';

export const tooltipProps = {
    isAnimationActive: false,
    wrapperStyle: { zIndex: 1000 },
    allowEscapeViewBox: { x: false, y: true },
} as const;

export function BarCursor({ x, y, width, height }: { x?: number; y?: number; width?: number; height?: number }) {
    if (x === undefined || y === undefined || width === undefined || height === undefined) return null;
    return <line x1={x + width / 2} y1={y} x2={x + width / 2} y2={y + height} stroke="currentColor" strokeWidth={1} className="stroke-border" />;
}

/**
 * Returns a Recharts dot renderer that only draws a dot for isolated points
 * (i.e. points with no non-null neighbour on either side). Useful for area
 * charts where gaps in the data would otherwise leave isolated values invisible.
 */
export function isolatedDot<T>(data: T[], key: keyof T, color: string) {
    return (props: { cx?: number; cy?: number; index?: number; value?: number | null }) => {
        const { cx, cy, index, value } = props;
        if (value == null || cx == null || cy == null || index == null) return null;
        const prev = data[index - 1]?.[key];
        const next = data[index + 1]?.[key];
        if (prev != null || next != null) return null;
        return <circle cx={cx} cy={cy} r={3} fill={color} />;
    };
}
import { ChartContainer, type ChartConfig } from '@/components/ui/chart';

interface ChartPanelProps {
    config: ChartConfig;
    title: string;
    heroValue: ReactNode;
    legendStats?: ReactNode;
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
    const wrapperRef = useRef<HTMLDivElement>(null);

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
            <div ref={wrapperRef}>
                <ChartContainer config={config} className="min-h-0 w-full flex-1 max-h-[270px]">
                    {children(legendContent) as React.ReactElement}
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
