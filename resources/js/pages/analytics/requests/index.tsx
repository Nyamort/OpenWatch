import { Head } from '@inertiajs/react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { ChartLegend, ChartTooltip, ChartTooltipContent, type ChartConfig } from '@/components/ui/chart';
import { ChartPanel } from '@/components/analytics/chart-panel';
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
    '2xx': { label: '1/2/3xx', color: 'var(--color-chart-2)' },
    '4xx': { label: '4xx', color: 'hsl(30 90% 55%)' },
    '5xx': { label: '5xx', color: 'hsl(0 72% 51%)' },
} satisfies ChartConfig;

const durationChartConfig = {
    avg: { label: 'AVG', color: 'var(--color-chart-1)' },
    p95: { label: 'P95', color: 'var(--color-chart-4)' },
} satisfies ChartConfig;

function formatDuration(us: number | null): string {
    if (us === null) return '—';
    const ms = us / 1000;
    if (ms >= 1000) return `${(ms / 1000).toFixed(2)}s`;
    return `${ms.toFixed(2)}ms`;
}

export default function RequestsIndex({ graph, stats, period }: Props) {
    const requestStats = (
        <div className="flex gap-4 text-sm">
            {(['2xx', '4xx', '5xx'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="text-muted-foreground flex items-center gap-1">
                        <span className="inline-block h-2 w-2 rounded-sm" style={{ backgroundColor: requestChartConfig[key].color }} />
                        {requestChartConfig[key].label}
                    </span>
                    <span className="font-medium tabular-nums">{stats[key].toLocaleString()}</span>
                </div>
            ))}
        </div>
    );

    const durationStats = (
        <div className="flex gap-4 text-sm">
            {(['avg', 'p95'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="text-muted-foreground flex items-center gap-1">
                        <span className="inline-block h-2 w-2 rounded-sm" style={{ backgroundColor: durationChartConfig[key].color }} />
                        {key.toUpperCase()}
                    </span>
                    <span className="font-medium tabular-nums">{formatDuration(stats[key])}</span>
                </div>
            ))}
        </div>
    );

    return (
        <AnalyticsLayout title="Requests" period={period} breadcrumbs={breadcrumbs}>
            <Head title="Requests" />

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
                        <BarChart data={graph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
                            <CartesianGrid vertical={false} strokeDasharray="3 3" className="stroke-border" />
                            <XAxis dataKey="bucket" hide />
                            <YAxis hide />
                            <ChartTooltip content={<ChartTooltipContent hideLabel />} />
                            <ChartLegend verticalAlign="top" content={legendContent} />
                            <Bar dataKey="2xx" stackId="a" fill={requestChartConfig['2xx'].color} radius={0} />
                            <Bar dataKey="4xx" stackId="a" fill={requestChartConfig['4xx'].color} radius={0} />
                            <Bar dataKey="5xx" stackId="a" fill={requestChartConfig['5xx'].color} radius={[3, 3, 0, 0]} />
                        </BarChart>
                    )}
                </ChartPanel>

                <ChartPanel
                    config={durationChartConfig}
                    title="Duration"
                    heroValue={`${formatDuration(stats.min)} – ${formatDuration(stats.max)}`}
                    legendStats={durationStats}
                    firstBucket={graph[0]?.bucket}
                    lastBucket={graph[graph.length - 1]?.bucket}
                >
                    {(legendContent) => (
                        <AreaChart data={graph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
                            <defs>
                                <linearGradient id="fillAvg" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor={durationChartConfig.avg.color} stopOpacity={0.3} />
                                    <stop offset="95%" stopColor={durationChartConfig.avg.color} stopOpacity={0} />
                                </linearGradient>
                                <linearGradient id="fillP95" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor={durationChartConfig.p95.color} stopOpacity={0.2} />
                                    <stop offset="95%" stopColor={durationChartConfig.p95.color} stopOpacity={0} />
                                </linearGradient>
                            </defs>
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
                            <ChartLegend verticalAlign="top" content={legendContent} />
                            <Area type="linear" dataKey="p95" stroke={durationChartConfig.p95.color} strokeWidth={2} fill="url(#fillP95)" dot={false} strokeDasharray="4 2" />
                            <Area type="linear" dataKey="avg" stroke={durationChartConfig.avg.color} strokeWidth={2} fill="url(#fillAvg)" dot={false} />
                        </AreaChart>
                    )}
                </ChartPanel>
            </div>
        </AnalyticsLayout>
    );
}
