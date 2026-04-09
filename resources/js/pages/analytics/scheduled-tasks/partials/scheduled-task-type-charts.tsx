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
import type {
    ScheduledTaskTypeGraphBucket,
    ScheduledTaskTypeStats,
} from '../types';

const runsChartConfig = {
    processed: { label: 'Processed', color: 'oklch(0.50 0 0)' },
    skipped: { label: 'Skipped', color: 'hsl(30 90% 55%)' },
    failed: { label: 'Failed', color: 'hsl(0 72% 51%)' },
} satisfies ChartConfig;

interface ScheduledTaskTypeChartsProps {
    graph: ScheduledTaskTypeGraphBucket[];
    stats: ScheduledTaskTypeStats;
}

export function ScheduledTaskTypeCharts({
    graph,
    stats,
}: ScheduledTaskTypeChartsProps) {
    const runsStats = (
        <div className="flex gap-4 text-sm">
            {(['processed', 'skipped', 'failed'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="flex items-center gap-1 text-muted-foreground">
                        <span
                            className="inline-block h-3 w-1 rounded-sm"
                            style={{
                                backgroundColor: runsChartConfig[key].color,
                            }}
                        />
                        {runsChartConfig[key].label}
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
                config={runsChartConfig}
                title="Runs"
                heroValue={stats.count.toLocaleString()}
                legendStats={runsStats}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart
                        syncId="scheduled-task-type"
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
                                            color: runsChartConfig.processed
                                                .color,
                                            label: 'Processed',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey ===
                                                        'processed',
                                                )?.value ?? 0,
                                        },
                                        {
                                            color: runsChartConfig.skipped
                                                .color,
                                            label: 'Skipped',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey === 'skipped',
                                                )?.value ?? 0,
                                        },
                                        {
                                            color: runsChartConfig.failed.color,
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
                            dataKey="processed"
                            stackId="a"
                            fill={runsChartConfig.processed.color}
                            radius={0}
                        />
                        <Bar
                            dataKey="skipped"
                            stackId="a"
                            fill={runsChartConfig.skipped.color}
                            radius={0}
                        />
                        <Bar
                            dataKey="failed"
                            stackId="a"
                            fill={runsChartConfig.failed.color}
                            radius={[3, 3, 0, 0]}
                        />
                    </BarChart>
                )}
            </ChartPanel>

            <DurationChartPanel
                graph={graph}
                stats={stats}
                syncId="scheduled-task-type"
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            />
        </div>
    );
}
