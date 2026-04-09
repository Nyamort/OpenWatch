import { useId } from 'react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import {
    ChartPanel,
    isolatedDot,
    tooltipProps,
} from '@/components/analytics/chart-panel';
import { AnalyticsTooltip } from '@/components/analytics/chart-tooltip';
import {
    ChartLegend,
    ChartTooltip,
    type ChartConfig,
} from '@/components/ui/chart';
import { formatDuration } from '@/lib/utils';

const durationChartConfig = {
    avg: { label: 'AVG', color: 'oklch(0.50 0 0)' },
    p95: { label: 'P95', color: 'hsl(30 90% 55%)' },
} satisfies ChartConfig;

interface DurationChartPanelProps {
    graph: Array<{ bucket: string; avg: number | null; p95: number | null }>;
    stats: {
        avg: number | null;
        p95: number | null;
        min?: number | null;
        max?: number | null;
    };
    syncId: string;
    firstBucket?: string;
    lastBucket?: string;
}

export function DurationChartPanel({
    graph,
    stats,
    syncId,
    firstBucket,
    lastBucket,
}: DurationChartPanelProps) {
    const id = useId();

    const heroValue =
        stats.min != null && stats.max != null
            ? `${formatDuration(stats.min)} – ${formatDuration(stats.max)}`
            : stats.avg != null
              ? formatDuration(stats.avg)
              : '—';

    const legendStats = (
        <div className="flex gap-4 text-sm">
            {(['avg', 'p95'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="flex items-center gap-1 text-muted-foreground">
                        <span
                            className="inline-block h-3 w-1 rounded-sm"
                            style={{
                                backgroundColor: durationChartConfig[key].color,
                            }}
                        />
                        {key.toUpperCase()}
                    </span>
                    <span className="font-medium tabular-nums">
                        {formatDuration(stats[key])}
                    </span>
                </div>
            ))}
        </div>
    );

    return (
        <ChartPanel
            config={durationChartConfig}
            title="Duration"
            heroValue={heroValue}
            legendStats={legendStats}
            firstBucket={firstBucket}
            lastBucket={lastBucket}
        >
            {(legendContent) => (
                <AreaChart
                    syncId={syncId}
                    data={graph}
                    margin={{ top: 0, right: 0, left: 0, bottom: 0 }}
                >
                    <defs>
                        <linearGradient
                            id={`${id}-avg`}
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="5%"
                                stopColor={durationChartConfig.avg.color}
                                stopOpacity={0.3}
                            />
                            <stop
                                offset="95%"
                                stopColor={durationChartConfig.avg.color}
                                stopOpacity={0}
                            />
                        </linearGradient>
                        <linearGradient
                            id={`${id}-p95`}
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="5%"
                                stopColor={durationChartConfig.p95.color}
                                stopOpacity={0.2}
                            />
                            <stop
                                offset="95%"
                                stopColor={durationChartConfig.p95.color}
                                stopOpacity={0}
                            />
                        </linearGradient>
                    </defs>
                    <CartesianGrid
                        vertical={false}
                        strokeDasharray="3 3"
                        className="stroke-border"
                    />
                    <XAxis dataKey="bucket" hide />
                    <YAxis hide domain={[0, 'auto']} />
                    <ChartTooltip
                        {...tooltipProps}
                        content={({ active, label, payload }) => (
                            <AnalyticsTooltip
                                active={active}
                                label={label}
                                rows={[
                                    {
                                        color: durationChartConfig.avg.color,
                                        label: 'AVG',
                                        value: formatDuration(
                                            (payload?.find(
                                                (p) => p.dataKey === 'avg',
                                            )?.value as number) ?? null,
                                        ),
                                    },
                                    {
                                        color: durationChartConfig.p95.color,
                                        label: 'P95',
                                        value: formatDuration(
                                            (payload?.find(
                                                (p) => p.dataKey === 'p95',
                                            )?.value as number) ?? null,
                                        ),
                                    },
                                ]}
                            />
                        )}
                    />
                    <ChartLegend verticalAlign="top" content={legendContent} />
                    <Area
                        type="linear"
                        dataKey="p95"
                        stroke={durationChartConfig.p95.color}
                        strokeWidth={2}
                        fill={`url(#${id}-p95)`}
                        dot={isolatedDot(
                            graph,
                            'p95',
                            durationChartConfig.p95.color,
                        )}
                    />
                    <Area
                        type="linear"
                        dataKey="avg"
                        stroke={durationChartConfig.avg.color}
                        strokeWidth={2}
                        fill={`url(#${id}-avg)`}
                        dot={isolatedDot(
                            graph,
                            'avg',
                            durationChartConfig.avg.color,
                        )}
                    />
                </AreaChart>
            )}
        </ChartPanel>
    );
}
