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

export interface QueryDetailGraphBucket {
    bucket: string;
    calls: number;
    avg: number | null;
    p95: number | null;
}

export interface QueryDetailStats {
    count: number;
    total: number | null;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
    sql_normalized: string | null;
    connection: string | null;
}

const callsChartConfig = {
    calls: { label: 'Calls', color: 'oklch(0.50 0 0)' },
} satisfies ChartConfig;

interface QueryDetailChartsProps {
    graph: QueryDetailGraphBucket[];
    stats: QueryDetailStats;
}

export function QueryDetailCharts({ graph, stats }: QueryDetailChartsProps) {
    const callsStats = (
        <div className="flex gap-4 text-sm">
            <div className="flex flex-col items-end gap-0.5">
                <span className="flex items-center gap-1 text-muted-foreground">
                    <span
                        className="inline-block h-3 w-1 rounded-sm"
                        style={{ backgroundColor: callsChartConfig.calls.color }}
                    />
                    Calls
                </span>
                <span className="font-medium tabular-nums">
                    {stats.count.toLocaleString()}
                </span>
            </div>
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
                        syncId="query-detail"
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
                                            color: callsChartConfig.calls.color,
                                            label: 'Calls',
                                            value: payload?.find((p) => p.dataKey === 'calls')?.value ?? 0,
                                        },
                                    ]}
                                />
                            )}
                        />
                        <ChartLegend verticalAlign="top" content={legendContent} />
                        <Bar
                            dataKey="calls"
                            fill={callsChartConfig.calls.color}
                            radius={[3, 3, 0, 0]}
                        />
                    </BarChart>
                )}
            </ChartPanel>

            <DurationChartPanel
                graph={graph}
                stats={stats}
                syncId="query-detail"
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            />
        </div>
    );
}
