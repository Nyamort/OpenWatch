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
import type { NotificationTypeGraphBucket, NotificationTypeStats } from '../types';

const notificationsChartConfig = {
    count: { label: 'Notifications', color: 'oklch(0.50 0 0)' },
} satisfies ChartConfig;

interface NotificationTypeChartsProps {
    graph: NotificationTypeGraphBucket[];
    stats: NotificationTypeStats;
}

export function NotificationTypeCharts({ graph, stats }: NotificationTypeChartsProps) {
    const notificationsStats = (
        <div className="flex gap-4 text-sm">
            <div className="flex flex-col items-end gap-0.5">
                <span className="flex items-center gap-1 text-muted-foreground">
                    <span
                        className="inline-block h-3 w-1 rounded-sm"
                        style={{ backgroundColor: notificationsChartConfig.count.color }}
                    />
                    Notifications
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
                config={notificationsChartConfig}
                title="Notifications"
                heroValue={stats.count.toLocaleString()}
                legendStats={notificationsStats}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart
                        syncId="notification-type"
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
                                            color: notificationsChartConfig.count.color,
                                            label: 'Notifications',
                                            value: payload?.find((p) => p.dataKey === 'count')?.value ?? 0,
                                        },
                                    ]}
                                />
                            )}
                        />
                        <ChartLegend verticalAlign="top" content={legendContent} />
                        <Bar
                            dataKey="count"
                            fill={notificationsChartConfig.count.color}
                            radius={[3, 3, 0, 0]}
                        />
                    </BarChart>
                )}
            </ChartPanel>

            <DurationChartPanel
                graph={graph}
                stats={stats}
                syncId="notification-type"
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            />
        </div>
    );
}
