import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import {
    BarCursor,
    ChartPanel,
    tooltipProps,
} from '@/components/analytics/chart-panel';
import { AnalyticsTooltip } from '@/components/analytics/chart-tooltip';
import type {
    ExceptionGraphBucket,
    ExceptionStats,
} from '@/components/exceptions/types';
import {
    ChartLegend,
    ChartTooltip,
    type ChartConfig,
} from '@/components/ui/chart';

const chartConfig = {
    handled: { label: 'Handled', color: 'oklch(0.50 0 0)' },
    unhandled: { label: 'Unhandled', color: 'hsl(0 72% 51%)' },
} satisfies ChartConfig;

interface ExceptionChartsProps {
    graph: ExceptionGraphBucket[];
    stats: ExceptionStats;
    syncId?: string;
}

export function ExceptionCharts({
    graph,
    stats,
    syncId,
}: ExceptionChartsProps) {
    const legendStats = (
        <div className="flex gap-4 text-sm">
            {(['handled', 'unhandled'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="flex items-center gap-1 text-muted-foreground">
                        <span
                            className="inline-block h-3 w-1 rounded-sm"
                            style={{ backgroundColor: chartConfig[key].color }}
                        />
                        {chartConfig[key].label}
                    </span>
                    <span className="font-medium tabular-nums">
                        {stats[key].toLocaleString()}
                    </span>
                </div>
            ))}
        </div>
    );

    return (
        <ChartPanel
            config={chartConfig}
            title="Occurrences"
            heroValue={stats.count.toLocaleString()}
            legendStats={legendStats}
            firstBucket={graph[0]?.bucket}
            lastBucket={graph[graph.length - 1]?.bucket}
        >
            {(legendContent) => (
                <BarChart
                    syncId={syncId}
                    data={graph}
                    margin={{ top: 0, right: 0, left: 0, bottom: 0 }}
                >
                    <CartesianGrid
                        vertical={false}
                        strokeDasharray="3 3"
                        className="stroke-border"
                    />
                    <XAxis dataKey="bucket" hide />
                    <YAxis hide />
                    <ChartTooltip
                        {...tooltipProps}
                        cursor={<BarCursor />}
                        content={({ active, label, payload }) => (
                            <AnalyticsTooltip
                                active={active}
                                label={label}
                                rows={[
                                    {
                                        color: chartConfig.handled.color,
                                        label: 'Handled',
                                        value:
                                            payload?.find(
                                                (p) => p.dataKey === 'handled',
                                            )?.value ?? 0,
                                    },
                                    {
                                        color: chartConfig.unhandled.color,
                                        label: 'Unhandled',
                                        value:
                                            payload?.find(
                                                (p) =>
                                                    p.dataKey === 'unhandled',
                                            )?.value ?? 0,
                                    },
                                ]}
                                footer={
                                    <div className="flex justify-between">
                                        <span>Total</span>
                                        <span className="font-medium tabular-nums">
                                            {payload?.reduce(
                                                (sum, p) =>
                                                    sum +
                                                    ((p.value as number) ?? 0),
                                                0,
                                            ) ?? 0}
                                        </span>
                                    </div>
                                }
                            />
                        )}
                    />
                    <ChartLegend verticalAlign="top" content={legendContent} />
                    <Bar
                        dataKey="handled"
                        stackId="a"
                        fill={chartConfig.handled.color}
                        radius={0}
                    />
                    <Bar
                        dataKey="unhandled"
                        stackId="a"
                        fill={chartConfig.unhandled.color}
                        radius={[3, 3, 0, 0]}
                    />
                </BarChart>
            )}
        </ChartPanel>
    );
}
