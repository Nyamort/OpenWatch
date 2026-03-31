import { Area, AreaChart, Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { BarCursor, ChartPanel, isolatedDot, tooltipProps } from '@/components/analytics/chart-panel';
import { AnalyticsTooltip } from '@/components/analytics/chart-tooltip';
import { ChartLegend, ChartTooltip, type ChartConfig } from '@/components/ui/chart';
import { formatDuration } from '@/lib/utils';
import type { MailGraphBucket, MailStats } from '../types';

const mailsChartConfig = {
    count: { label: 'Mails', color: 'oklch(0.50 0 0)' },
} satisfies ChartConfig;

const durationChartConfig = {
    avg: { label: 'AVG', color: 'oklch(0.50 0 0)' },
    p95: { label: 'P95', color: 'hsl(30 90% 55%)' },
} satisfies ChartConfig;

interface MailChartsProps {
    graph: MailGraphBucket[];
    stats: MailStats;
}

export function MailCharts({ graph, stats }: MailChartsProps) {
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
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartPanel
                config={mailsChartConfig}
                title="Mails"
                heroValue={stats.count.toLocaleString()}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart syncId="mails" data={graph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
                        <CartesianGrid vertical={false} strokeDasharray="3 3" className="stroke-border" />
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
                                        { color: mailsChartConfig.count.color, label: 'Mails', value: payload?.find(p => p.dataKey === 'count')?.value ?? 0 },
                                    ]}
                                />
                            )}
                        />
                        <ChartLegend verticalAlign="top" content={legendContent} />
                        <Bar dataKey="count" fill={mailsChartConfig.count.color} radius={[3, 3, 0, 0]} />
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
                    <AreaChart syncId="mails" data={graph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
                        <defs>
                            <linearGradient id="fillMailAvg" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor={durationChartConfig.avg.color} stopOpacity={0.3} />
                                <stop offset="95%" stopColor={durationChartConfig.avg.color} stopOpacity={0} />
                            </linearGradient>
                            <linearGradient id="fillMailP95" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor={durationChartConfig.p95.color} stopOpacity={0.2} />
                                <stop offset="95%" stopColor={durationChartConfig.p95.color} stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} strokeDasharray="3 3" className="stroke-border" />
                        <XAxis dataKey="bucket" hide />
                        <YAxis hide domain={[0, 'auto']} />
                        <ChartTooltip
                            {...tooltipProps}
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
                        <Area type="linear" dataKey="p95" stroke={durationChartConfig.p95.color} strokeWidth={2} fill="url(#fillMailP95)" dot={isolatedDot(graph, 'p95', durationChartConfig.p95.color)} />
                        <Area type="linear" dataKey="avg" stroke={durationChartConfig.avg.color} strokeWidth={2} fill="url(#fillMailAvg)" dot={isolatedDot(graph, 'avg', durationChartConfig.avg.color)} />
                    </AreaChart>
                )}
            </ChartPanel>
        </div>
    );
}
