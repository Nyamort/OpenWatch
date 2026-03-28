import { Head } from '@inertiajs/react';
import { ArrowUpRight, Globe, PanelRight } from 'lucide-react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { ChartPanel, isolatedDot } from '@/components/analytics/chart-panel';
import { AnalyticsTooltip } from '@/components/analytics/chart-tooltip';
import { ChartLegend, ChartTooltip, type ChartConfig } from '@/components/ui/chart';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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

interface PathRow {
    methods: string[];
    path: string | null;
    '2xx': number;
    '4xx': number;
    '5xx': number;
    total: number;
    avg: number | null;
    p95: number | null;
}

interface Props {
    graph: GraphBucket[];
    stats: Stats;
    paths: PathRow[];
    period: string;
}

const breadcrumbs = [{ title: 'Requests', href: '#' }];

const requestChartConfig = {
    '2xx': { label: '1/2/3xx', color: 'oklch(0.50 0 0)' },
    '4xx': { label: '4xx', color: 'hsl(30 90% 55%)' },
    '5xx': { label: '5xx', color: 'hsl(0 72% 51%)' },
} satisfies ChartConfig;

const durationChartConfig = {
    avg: { label: 'AVG', color: 'oklch(0.50 0 0)' },
    p95: { label: 'P95', color: 'hsl(30 90% 55%)' },
} satisfies ChartConfig;

function formatDuration(us: number | null): string {
    if (us === null) return '—';
    const ms = us / 1000;
    if (ms >= 1000) return `${(ms / 1000).toFixed(2)}s`;
    return `${ms.toFixed(2)}ms`;
}

function BarCursor({ x, y, width, height }: { x?: number; y?: number; width?: number; height?: number }) {
    if (x === undefined || y === undefined || width === undefined || height === undefined) return null;
    return <line x1={x + width / 2} y1={y} x2={x + width / 2} y2={y + height} stroke="currentColor" strokeWidth={1} className="stroke-border" />;
}

export default function RequestsIndex({ graph, stats, paths, period }: Props) {
    const requestStats = (
        <div className="flex gap-4 text-sm">
            {(['2xx', '4xx', '5xx'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="text-muted-foreground flex items-center gap-1">
                        <span className="inline-block h-3 w-1 rounded-sm" style={{ backgroundColor: requestChartConfig[key].color }} />
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
                        <span className="inline-block h-3 w-1 rounded-sm" style={{ backgroundColor: durationChartConfig[key].color }} />
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
                        <BarChart syncId="requests" data={graph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
                            <CartesianGrid vertical={false} strokeDasharray="3 3" className="stroke-border" />
                            <XAxis dataKey="bucket" hide />
                            <YAxis hide />
                            <ChartTooltip
                                isAnimationActive={false}
                                cursor={<BarCursor />}
                                allowEscapeViewBox={{ x: false, y: true }}
                                content={({ active, label, payload }) => (
                                    <AnalyticsTooltip
                                        active={active}
                                        label={label}
                                        rows={[
                                            { color: requestChartConfig['2xx'].color, label: '1/2/3xx', value: payload?.find(p => p.dataKey === '2xx')?.value ?? 0 },
                                            { color: requestChartConfig['4xx'].color, label: '4xx', value: payload?.find(p => p.dataKey === '4xx')?.value ?? 0 },
                                            { color: requestChartConfig['5xx'].color, label: '5xx', value: payload?.find(p => p.dataKey === '5xx')?.value ?? 0 },
                                        ]}
                                        footer={
                                            <div className="flex justify-between">
                                                <span>Total</span>
                                                <span className="font-medium tabular-nums">
                                                    {payload?.reduce((sum, p) => sum + ((p.value as number) ?? 0), 0) ?? 0}
                                                </span>
                                            </div>
                                        }
                                    />
                                )}
                            />
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
                    heroValue={stats.min !== null && stats.max !== null ? `${formatDuration(stats.min)} – ${formatDuration(stats.max)}` : '—'}
                    legendStats={durationStats}
                    firstBucket={graph[0]?.bucket}
                    lastBucket={graph[graph.length - 1]?.bucket}
                >
                    {(legendContent) => (
                        <AreaChart syncId="requests" data={graph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
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
                            <YAxis hide domain={[0, 'auto']} />
                            <ChartTooltip
                                isAnimationActive={false}
                                allowEscapeViewBox={{ x: false, y: true }}
                                content={({ active, label, payload }) => (
                                    <AnalyticsTooltip
                                        active={active}
                                        label={label}
                                        rows={[
                                            { color: durationChartConfig.avg.color, label: 'AVG', value: formatDuration(payload?.find(p => p.dataKey === 'avg')?.value as number ?? null) },
                                            { color: durationChartConfig.p95.color, label: 'P95', value: formatDuration(payload?.find(p => p.dataKey === 'p95')?.value as number ?? null) },
                                        ]}
                                    />
                                )}
                            />
                            <ChartLegend verticalAlign="top" content={legendContent} />
                            <Area type="linear" dataKey="p95" stroke={durationChartConfig.p95.color} strokeWidth={2} fill="url(#fillP95)" dot={isolatedDot(graph, 'p95', durationChartConfig.p95.color)} />
                            <Area type="linear" dataKey="avg" stroke={durationChartConfig.avg.color} strokeWidth={2} fill="url(#fillAvg)" dot={isolatedDot(graph, 'avg', durationChartConfig.avg.color)} />
                        </AreaChart>
                    )}
                </ChartPanel>
            </div>
            <Table className="border-separate border-spacing-y-1.5">
                <TableHeader className="[&_tr]:border-0">
                    <TableRow className="border-0 hover:bg-transparent">
                        <TableHead className="h-8 w-px whitespace-nowrap pl-5 text-xs font-medium uppercase tracking-wide">Method</TableHead>
                        <TableHead className="h-8 px-4 text-xs font-medium uppercase tracking-wide">Path</TableHead>
                        <TableHead className="h-8 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">1/2/3xx</TableHead>
                        <TableHead className="h-8 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">4xx</TableHead>
                        <TableHead className="h-8 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">5xx</TableHead>
                        <TableHead className="h-8 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">Total</TableHead>
                        <TableHead className="h-8 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">AVG</TableHead>
                        <TableHead className="h-8 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">P95</TableHead>
                        <TableHead className="h-8 w-px pr-5" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {paths.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell colSpan={9} className="py-12 text-center text-sm text-muted-foreground">
                                No requests recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        paths.map((row, i) => (
                            <TableRow
                                key={i}
                                className="bg-surface group/row border-0 hover:bg-transparent cursor-pointer shadow-sm shadow-black/4 [&_td]:border-y [&_td]:border-border [&_td:first-child]:border-l [&_td:first-child]:rounded-l-lg [&_td:last-child]:border-r [&_td:last-child]:rounded-r-lg [&_td]:bg-surface hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td]:transition-colors [&_td]:duration-150"
                            >
                                <TableCell className="h-11 w-px whitespace-nowrap pl-5 pr-4">
                                    <span className="font-mono text-xs font-semibold text-muted-foreground">
                                        {row.methods.length === 0 ? 'ANY' : row.methods.join(' | ')}
                                    </span>
                                </TableCell>
                                <TableCell className="h-11 overflow-hidden px-4">
                                    <div className="flex min-w-0 items-center gap-2">
                                        <Globe className="size-4 shrink-0 stroke-1 text-muted-foreground" />
                                        <span className="truncate font-mono text-sm">
                                            {row.path ?? 'Unmatched Route'}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row['2xx'].toLocaleString()}
                                </TableCell>
                                <TableCell className={`h-11 w-px whitespace-nowrap px-4 text-right tabular-nums ${row['4xx'] === 0 ? 'text-muted-foreground' : ''}`}>
                                    {row['4xx'].toLocaleString()}
                                </TableCell>
                                <TableCell className={`h-11 w-px whitespace-nowrap px-4 text-right tabular-nums ${row['5xx'] === 0 ? 'text-muted-foreground' : 'text-red-500'}`}>
                                    {row['5xx'].toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums font-medium">
                                    {row.total.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {formatDuration(row.avg)}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {formatDuration(row.p95)}
                                </TableCell>
                                <TableCell className="h-11 pr-5">
                                    <div className="flex items-center justify-end">
                                        <div className="flex items-center rounded-sm border border-border/50 bg-muted/30 text-muted-foreground opacity-0 transition-opacity group-hover/row:opacity-100">
                                            <button className="flex size-6 items-center justify-center hover:text-foreground">
                                                <PanelRight className="size-3" />
                                            </button>
                                            <button className="flex size-6 items-center justify-center hover:text-foreground">
                                                <ArrowUpRight className="size-4" />
                                            </button>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
        </AnalyticsLayout>
    );
}
