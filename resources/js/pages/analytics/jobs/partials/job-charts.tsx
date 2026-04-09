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
import type { JobGraphBucket, JobStats } from '../types';

const attemptsChartConfig = {
    processed: { label: 'Processed', color: 'oklch(0.50 0 0)' },
    failed: { label: 'Failed', color: 'hsl(0 72% 51%)' },
    released: { label: 'Released', color: 'hsl(30 90% 55%)' },
} satisfies ChartConfig;

interface JobChartsProps {
    graph: JobGraphBucket[];
    stats: JobStats;
}

export function JobCharts({ graph, stats }: JobChartsProps) {
    const attemptsStats = (
        <div className="flex gap-4 text-sm">
            {(['processed', 'failed', 'released'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="flex items-center gap-1 text-muted-foreground">
                        <span
                            className="inline-block h-3 w-1 rounded-sm"
                            style={{
                                backgroundColor: attemptsChartConfig[key].color,
                            }}
                        />
                        {attemptsChartConfig[key].label}
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
                config={attemptsChartConfig}
                title="Attempts"
                heroValue={stats.count.toLocaleString()}
                legendStats={attemptsStats}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart
                        syncId="jobs"
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
                                            color: attemptsChartConfig.processed
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
                                            color: attemptsChartConfig.failed
                                                .color,
                                            label: 'Failed',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey === 'failed',
                                                )?.value ?? 0,
                                        },
                                        {
                                            color: attemptsChartConfig.released
                                                .color,
                                            label: 'Released',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey ===
                                                        'released',
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
                            fill={attemptsChartConfig.processed.color}
                            radius={0}
                        />
                        <Bar
                            dataKey="released"
                            stackId="a"
                            fill={attemptsChartConfig.released.color}
                            radius={0}
                        />
                        <Bar
                            dataKey="failed"
                            stackId="a"
                            fill={attemptsChartConfig.failed.color}
                            radius={[3, 3, 0, 0]}
                        />
                    </BarChart>
                )}
            </ChartPanel>

            <DurationChartPanel
                graph={graph}
                stats={stats}
                syncId="jobs"
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            />
        </div>
    );
}
