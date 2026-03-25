import { Head } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, Line, LineChart, XAxis, YAxis } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig } from '@/components/ui/chart';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface GraphBucket {
    bucket: string;
    count: number;
    '2xx': number;
    '4xx': number;
    '5xx': number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

interface Stats {
    count: number;
    '2xx': number;
    '4xx': number;
    '5xx': number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

interface Props {
    graph: GraphBucket[];
    stats: Stats;
    period: string;
}

const breadcrumbs = [{ title: 'Requests', href: '#' }];

const requestChartConfig = {
    '2xx': { label: '2xx', color: 'var(--color-chart-2)' },
    '4xx': { label: '4xx', color: 'hsl(30 90% 55%)' },
    '5xx': { label: '5xx', color: 'hsl(0 72% 51%)' },
} satisfies ChartConfig;

const durationChartConfig = {
    avg: { label: 'Avg', color: 'var(--color-chart-1)' },
    p95: { label: 'p95', color: 'var(--color-chart-4)' },
} satisfies ChartConfig;

function formatDuration(us: number | null): string {
    if (us === null) return '—';
    const ms = us / 1000;
    if (ms >= 1000) return `${(ms / 1000).toFixed(2)}s`;
    return `${ms.toFixed(2)}ms`;
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

export default function RequestsIndex({ graph, stats, period }: Props) {
    const totalRequests = stats.count;

    return (
        <AnalyticsLayout title="Requests" period={period} breadcrumbs={breadcrumbs}>
            <Head title="Requests" />

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                {/* Requests Panel */}
                <div className="bg-card flex flex-col rounded-xl border p-5">
                    <div className="mb-4 flex items-start justify-between">
                        <div>
                            <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Requests</p>
                            <p className="mt-1 font-bold tabular-nums">{totalRequests.toLocaleString()}</p>
                        </div>
                        <div className="grid grid-cols-3 gap-x-4 text-sm">
                            <span className="text-muted-foreground flex items-center justify-end gap-1">
                                <span className="inline-block h-2 w-2 rounded-sm" style={{ backgroundColor: requestChartConfig['2xx'].color }} />
                                2xx
                            </span>
                            <span className="text-muted-foreground flex items-center justify-end gap-1">
                                <span className="inline-block h-2 w-2 rounded-sm" style={{ backgroundColor: requestChartConfig['4xx'].color }} />
                                4xx
                            </span>
                            <span className="text-muted-foreground flex items-center justify-end gap-1">
                                <span className="inline-block h-2 w-2 rounded-sm" style={{ backgroundColor: requestChartConfig['5xx'].color }} />
                                5xx
                            </span>
                            <span className="font-medium tabular-nums text-right">{stats['2xx'].toLocaleString()}</span>
                            <span className="font-medium tabular-nums text-right">{stats['4xx'].toLocaleString()}</span>
                            <span className="font-medium tabular-nums text-right">{stats['5xx'].toLocaleString()}</span>
                        </div>
                    </div>

                    <ChartContainer config={requestChartConfig} className="min-h-0 w-full flex-1 max-h-[270px]">
                        <BarChart data={graph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
                            <CartesianGrid vertical={false} strokeDasharray="3 3" className="stroke-border" />
                            <XAxis dataKey="bucket" hide />
                            <YAxis hide />
                            <ChartTooltip content={<ChartTooltipContent hideLabel />} />
                            <Bar dataKey="2xx" stackId="a" fill={requestChartConfig['2xx'].color} radius={0} />
                            <Bar dataKey="4xx" stackId="a" fill={requestChartConfig['4xx'].color} radius={0} />
                            <Bar dataKey="5xx" stackId="a" fill={requestChartConfig['5xx'].color} radius={[3, 3, 0, 0]} />
                        </BarChart>
                    </ChartContainer>
                    {graph.length > 0 && (
                        <div className="text-muted-foreground mt-1 flex justify-between text-xs">
                            <span>{formatBucketDatetime(graph[0].bucket)}</span>
                            <span>{formatBucketDatetime(graph[graph.length - 1].bucket)}</span>
                        </div>
                    )}
                </div>

                {/* Duration Panel */}
                <div className="bg-card flex flex-col rounded-xl border p-5">
                    <div className="mb-4 flex items-start justify-between">
                        <div>
                            <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Duration</p>
                            <p className="mt-1 font-bold tabular-nums">{formatDuration(stats.min)} – {formatDuration(stats.max)}</p>
                        </div>
                        <div className="grid grid-cols-2 gap-x-4 text-sm">
                            <span className="text-muted-foreground flex items-center justify-end gap-1">
                                <span className="inline-block h-2 w-2 rounded-sm" style={{ backgroundColor: durationChartConfig.avg.color }} />
                                AVG
                            </span>
                            <span className="text-muted-foreground flex items-center justify-end gap-1">
                                <span className="inline-block h-2 w-2 rounded-sm" style={{ backgroundColor: durationChartConfig.p95.color }} />
                                P95
                            </span>
                            <span className="font-medium tabular-nums text-right">{formatDuration(stats.avg)}</span>
                            <span className="font-medium tabular-nums text-right">{formatDuration(stats.p95)}</span>
                        </div>
                    </div>

                    <ChartContainer config={durationChartConfig} className="min-h-0 w-full flex-1 max-h-[270px]">
                        <LineChart data={graph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
                            <CartesianGrid vertical={false} strokeDasharray="3 3" className="stroke-border" />
                            <XAxis dataKey="bucket" hide />
                            <YAxis hide />
                            <ChartTooltip
                                content={
                                    <ChartTooltipContent
                                        hideLabel
                                        formatter={(value) => formatDuration(value as number)}
                                    />
                                }
                            />
                            <Line
                                type="monotone"
                                dataKey="avg"
                                stroke={durationChartConfig.avg.color}
                                strokeWidth={2}
                                dot={false}
                                connectNulls
                            />
                            <Line
                                type="monotone"
                                dataKey="p95"
                                stroke={durationChartConfig.p95.color}
                                strokeWidth={2}
                                dot={false}
                                connectNulls
                                strokeDasharray="4 2"
                            />
                        </LineChart>
                    </ChartContainer>
                    {graph.length > 0 && (
                        <div className="text-muted-foreground mt-1 flex justify-between text-xs">
                            <span>{formatBucketDatetime(graph[0].bucket)}</span>
                            <span>{formatBucketDatetime(graph[graph.length - 1].bucket)}</span>
                        </div>
                    )}
                </div>
            </div>
        </AnalyticsLayout>
    );
}
