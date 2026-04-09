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
import type { MailGraphBucket, MailStats } from '../types';

const mailsChartConfig = {
    count: { label: 'Mails', color: 'oklch(0.50 0 0)' },
} satisfies ChartConfig;

interface MailChartsProps {
    graph: MailGraphBucket[];
    stats: MailStats;
}

export function MailCharts({ graph, stats }: MailChartsProps) {
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
                    <BarChart
                        syncId="mails"
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
                                            color: mailsChartConfig.count.color,
                                            label: 'Mails',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey === 'count',
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
                            dataKey="count"
                            fill={mailsChartConfig.count.color}
                            radius={[3, 3, 0, 0]}
                        />
                    </BarChart>
                )}
            </ChartPanel>

            <DurationChartPanel
                graph={graph}
                stats={stats}
                syncId="mails"
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            />
        </div>
    );
}
