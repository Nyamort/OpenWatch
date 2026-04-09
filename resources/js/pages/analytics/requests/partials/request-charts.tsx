import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import {
    BarCursor,
    ChartPanel,
    tooltipProps,
} from '@/components/analytics/chart-panel';
import { AnalyticsTooltip } from '@/components/analytics/chart-tooltip';
import { DurationChartPanel } from '@/components/analytics/duration-chart-panel';
import {
    ChartLegend,
    ChartTooltip,
    type ChartConfig,
} from '@/components/ui/chart';
import type { GraphBucket, Stats } from '../types';

const requestChartConfig = {
    '2xx': { label: '1/2/3xx', color: 'oklch(0.50 0 0)' },
    '4xx': { label: '4xx', color: 'hsl(30 90% 55%)' },
    '5xx': { label: '5xx', color: 'hsl(0 72% 51%)' },
} satisfies ChartConfig;

interface RequestChartsProps {
    graph: GraphBucket[];
    stats: Stats;
}

export function RequestCharts({ graph, stats }: RequestChartsProps) {
    const requestStats = (
        <div className="flex gap-4 text-sm">
            {(['2xx', '4xx', '5xx'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="flex items-center gap-1 text-muted-foreground">
                        <span
                            className="inline-block h-3 w-1 rounded-sm"
                            style={{
                                backgroundColor: requestChartConfig[key].color,
                            }}
                        />
                        {requestChartConfig[key].label}
                    </span>
                    <span className="font-medium tabular-nums">
                        {stats[key].toLocaleString()}
                    </span>
                </div>
            ))}
        </div>
    );

    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartPanel
                config={requestChartConfig}
                title="Requests"
                heroValue={stats.count.toLocaleString()}
                legendStats={requestStats}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart
                        syncId="requests"
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
                                            color: requestChartConfig['2xx']
                                                .color,
                                            label: '1/2/3xx',
                                            value:
                                                payload?.find(
                                                    (p) => p.dataKey === '2xx',
                                                )?.value ?? 0,
                                        },
                                        {
                                            color: requestChartConfig['4xx']
                                                .color,
                                            label: '4xx',
                                            value:
                                                payload?.find(
                                                    (p) => p.dataKey === '4xx',
                                                )?.value ?? 0,
                                        },
                                        {
                                            color: requestChartConfig['5xx']
                                                .color,
                                            label: '5xx',
                                            value:
                                                payload?.find(
                                                    (p) => p.dataKey === '5xx',
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
                                                        ((p.value as number) ??
                                                            0),
                                                    0,
                                                ) ?? 0}
                                            </span>
                                        </div>
                                    }
                                />
                            )}
                        />
                        <ChartLegend
                            verticalAlign="top"
                            content={legendContent}
                        />
                        <Bar
                            dataKey="2xx"
                            stackId="a"
                            fill={requestChartConfig['2xx'].color}
                            radius={0}
                        />
                        <Bar
                            dataKey="4xx"
                            stackId="a"
                            fill={requestChartConfig['4xx'].color}
                            radius={0}
                        />
                        <Bar
                            dataKey="5xx"
                            stackId="a"
                            fill={requestChartConfig['5xx'].color}
                            radius={[3, 3, 0, 0]}
                        />
                    </BarChart>
                )}
            </ChartPanel>

            <DurationChartPanel
                graph={graph}
                stats={stats}
                syncId="requests"
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            />
        </div>
    );
}
