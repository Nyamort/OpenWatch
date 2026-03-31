import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    XAxis,
    YAxis,
} from 'recharts';
import {
    BarCursor,
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
import type {
    OutgoingRequestGraphBucket,
    OutgoingRequestStats,
} from '../types';

const requestsChartConfig = {
    success: { label: '1/2/3xx', color: 'oklch(0.50 0 0)' },
    count_4xx: { label: '4xx', color: 'hsl(30 90% 55%)' },
    count_5xx: { label: '5xx', color: 'hsl(0 72% 51%)' },
} satisfies ChartConfig;

const durationChartConfig = {
    avg: { label: 'AVG', color: 'oklch(0.50 0 0)' },
    p95: { label: 'P95', color: 'hsl(30 90% 55%)' },
} satisfies ChartConfig;

interface OutgoingRequestChartsProps {
    graph: OutgoingRequestGraphBucket[];
    stats: OutgoingRequestStats;
}

export function OutgoingRequestCharts({
    graph,
    stats,
}: OutgoingRequestChartsProps) {
    const requestsStats = (
        <div className="flex gap-4 text-sm">
            {(['success', 'count_4xx', 'count_5xx'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="flex items-center gap-1 text-muted-foreground">
                        <span
                            className="inline-block h-3 w-1 rounded-sm"
                            style={{
                                backgroundColor: requestsChartConfig[key].color,
                            }}
                        />
                        {requestsChartConfig[key].label}
                    </span>
                    <span className="font-medium tabular-nums">
                        {stats[key].toLocaleString()}
                    </span>
                </div>
            ))}
        </div>
    );

    const durationStats = (
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
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartPanel
                config={requestsChartConfig}
                title="Requests"
                heroValue={stats.total.toLocaleString()}
                legendStats={requestsStats}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart
                        syncId="outgoing"
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
                                            color: requestsChartConfig.success
                                                .color,
                                            label: '1/2/3xx',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey === 'success',
                                                )?.value ?? 0,
                                        },
                                        {
                                            color: requestsChartConfig.count_4xx
                                                .color,
                                            label: '4xx',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey ===
                                                        'count_4xx',
                                                )?.value ?? 0,
                                        },
                                        {
                                            color: requestsChartConfig.count_5xx
                                                .color,
                                            label: '5xx',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey ===
                                                        'count_5xx',
                                                )?.value ?? 0,
                                        },
                                    ]}
                                />
                            )}
                        />
                        <ChartLegend
                            verticalAlign="top"
                            content={legendContent}
                        />
                        <Bar
                            dataKey="success"
                            stackId="a"
                            fill={requestsChartConfig.success.color}
                        />
                        <Bar
                            dataKey="count_4xx"
                            stackId="a"
                            fill={requestsChartConfig.count_4xx.color}
                        />
                        <Bar
                            dataKey="count_5xx"
                            stackId="a"
                            fill={requestsChartConfig.count_5xx.color}
                            radius={[3, 3, 0, 0]}
                        />
                    </BarChart>
                )}
            </ChartPanel>

            <ChartPanel
                config={durationChartConfig}
                title="Duration"
                heroValue={
                    stats.min !== null && stats.max !== null
                        ? `${formatDuration(stats.min)} – ${formatDuration(stats.max)}`
                        : '—'
                }
                legendStats={durationStats}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <AreaChart
                        syncId="outgoing"
                        data={graph}
                        margin={{ top: 0, right: 0, left: 0, bottom: 0 }}
                    >
                        <defs>
                            <linearGradient
                                id="fillOutgoingAvg"
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
                                id="fillOutgoingP95"
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
                                            color: durationChartConfig.avg
                                                .color,
                                            label: 'AVG',
                                            value: formatDuration(
                                                (payload?.find(
                                                    (p) => p.dataKey === 'avg',
                                                )?.value as number) ?? null,
                                            ),
                                        },
                                        {
                                            color: durationChartConfig.p95
                                                .color,
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
                        <ChartLegend
                            verticalAlign="top"
                            content={legendContent}
                        />
                        <Area
                            type="linear"
                            dataKey="p95"
                            stroke={durationChartConfig.p95.color}
                            strokeWidth={2}
                            fill="url(#fillOutgoingP95)"
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
                            fill="url(#fillOutgoingAvg)"
                            dot={isolatedDot(
                                graph,
                                'avg',
                                durationChartConfig.avg.color,
                            )}
                        />
                    </AreaChart>
                )}
            </ChartPanel>
        </div>
    );
}
