import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { BarCursor, ChartPanel, tooltipProps } from '@/components/analytics/chart-panel';
import { AnalyticsTooltip } from '@/components/analytics/chart-tooltip';
import { ChartLegend, ChartTooltip, type ChartConfig } from '@/components/ui/chart';
import type { CacheEventsGraphBucket, CacheFailuresGraphBucket, CacheStats } from '../types';

const eventsChartConfig = {
    hits: { label: 'Hits', color: 'oklch(0.60 0.15 145)' },
    misses: { label: 'Misses', color: 'oklch(0.50 0 0)' },
    writes: { label: 'Writes', color: 'oklch(0.55 0.15 230)' },
    deletes: { label: 'Deletes', color: 'oklch(0.65 0.10 60)' },
} satisfies ChartConfig;

const failuresChartConfig = {
    write_failures: { label: 'Write', color: 'hsl(0 80% 55%)' },
    delete_failures: { label: 'Delete', color: 'hsl(30 90% 55%)' },
} satisfies ChartConfig;

interface CacheChartsProps {
    eventsGraph: CacheEventsGraphBucket[];
    failuresGraph: CacheFailuresGraphBucket[];
    stats: CacheStats;
}

export function CacheCharts({ eventsGraph, failuresGraph, stats }: CacheChartsProps) {
    const eventsStats = (
        <div className="flex gap-4 text-sm">
            {(['hits', 'misses', 'writes', 'deletes'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="text-muted-foreground flex items-center gap-1">
                        <span className="inline-block h-3 w-1 rounded-sm" style={{ backgroundColor: eventsChartConfig[key].color }} />
                        {key.charAt(0).toUpperCase() + key.slice(1)}
                    </span>
                    <span className="font-medium tabular-nums">{stats[key].toLocaleString()}</span>
                </div>
            ))}
        </div>
    );

    const failuresStats = (
        <div className="flex gap-4 text-sm">
            <div className="flex flex-col items-end gap-0.5">
                <span className="text-muted-foreground flex items-center gap-1">
                    <span className="inline-block h-3 w-1 rounded-sm" style={{ backgroundColor: failuresChartConfig.write_failures.color }} />
                    Total
                </span>
                <span className="font-medium tabular-nums">{stats.failures.toLocaleString()}</span>
            </div>
        </div>
    );

    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartPanel
                config={eventsChartConfig}
                title="Events"
                heroValue={stats.total.toLocaleString()}
                legendStats={eventsStats}
                firstBucket={eventsGraph[0]?.bucket}
                lastBucket={eventsGraph[eventsGraph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart syncId="cache" data={eventsGraph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
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
                                        { color: eventsChartConfig.hits.color, label: 'Hits', value: payload?.find(p => p.dataKey === 'hits')?.value ?? 0 },
                                        { color: eventsChartConfig.misses.color, label: 'Misses', value: payload?.find(p => p.dataKey === 'misses')?.value ?? 0 },
                                        { color: eventsChartConfig.writes.color, label: 'Writes', value: payload?.find(p => p.dataKey === 'writes')?.value ?? 0 },
                                        { color: eventsChartConfig.deletes.color, label: 'Deletes', value: payload?.find(p => p.dataKey === 'deletes')?.value ?? 0 },
                                    ]}
                                />
                            )}
                        />
                        <ChartLegend verticalAlign="top" content={legendContent} />
                        <Bar dataKey="hits" stackId="a" fill={eventsChartConfig.hits.color} />
                        <Bar dataKey="misses" stackId="a" fill={eventsChartConfig.misses.color} />
                        <Bar dataKey="writes" stackId="a" fill={eventsChartConfig.writes.color} />
                        <Bar dataKey="deletes" stackId="a" fill={eventsChartConfig.deletes.color} radius={[3, 3, 0, 0]} />
                    </BarChart>
                )}
            </ChartPanel>

            <ChartPanel
                config={failuresChartConfig}
                title="Failures"
                heroValue={stats.failures.toLocaleString()}
                legendStats={failuresStats}
                firstBucket={failuresGraph[0]?.bucket}
                lastBucket={failuresGraph[failuresGraph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart syncId="cache" data={failuresGraph} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
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
                                        { color: failuresChartConfig.write_failures.color, label: 'Write', value: payload?.find(p => p.dataKey === 'write_failures')?.value ?? 0 },
                                        { color: failuresChartConfig.delete_failures.color, label: 'Delete', value: payload?.find(p => p.dataKey === 'delete_failures')?.value ?? 0 },
                                    ]}
                                />
                            )}
                        />
                        <ChartLegend verticalAlign="top" content={legendContent} />
                        <Bar dataKey="write_failures" stackId="b" fill={failuresChartConfig.write_failures.color} />
                        <Bar dataKey="delete_failures" stackId="b" fill={failuresChartConfig.delete_failures.color} radius={[3, 3, 0, 0]} />
                    </BarChart>
                )}
            </ChartPanel>
        </div>
    );
}
