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
import type { CommandGraphBucket, CommandStats } from '../types';

const callsChartConfig = {
    successful: { label: 'Successful', color: 'oklch(0.50 0 0)' },
    failed: { label: 'Failed', color: 'hsl(0 72% 51%)' },
} satisfies ChartConfig;

interface CommandChartsProps {
    graph: CommandGraphBucket[];
    stats: CommandStats;
}

export function CommandCharts({ graph, stats }: CommandChartsProps) {
    const callsStats = (
        <div className="flex gap-4 text-sm">
            {(['successful', 'failed'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="flex items-center gap-1 text-muted-foreground">
                        <span
                            className="inline-block h-3 w-1 rounded-sm"
                            style={{
                                backgroundColor: callsChartConfig[key].color,
                            }}
                        />
                        {callsChartConfig[key].label}
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
                config={callsChartConfig}
                title="Calls"
                heroValue={stats.count.toLocaleString()}
                legendStats={callsStats}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart
                        syncId="commands"
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
                                            color: callsChartConfig.successful
                                                .color,
                                            label: 'Successful',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey ===
                                                        'successful',
                                                )?.value ?? 0,
                                        },
                                        {
                                            color: callsChartConfig.failed
                                                .color,
                                            label: 'Failed',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey === 'failed',
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
                            dataKey="successful"
                            stackId="a"
                            fill={callsChartConfig.successful.color}
                            radius={0}
                        />
                        <Bar
                            dataKey="failed"
                            stackId="a"
                            fill={callsChartConfig.failed.color}
                            radius={[3, 3, 0, 0]}
                        />
                    </BarChart>
                )}
            </ChartPanel>

            <DurationChartPanel
                graph={graph}
                stats={stats}
                syncId="commands"
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            />
        </div>
    );
}
