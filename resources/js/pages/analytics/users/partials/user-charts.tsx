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
import type { GraphBucket, Stats } from '../types';

const usersChartConfig = {
    authenticated_users: {
        label: 'Authenticated Users',
        color: 'hsl(142 71% 45%)',
    },
} satisfies ChartConfig;

const requestsChartConfig = {
    authenticated: { label: 'Authenticated', color: 'hsl(142 71% 45%)' },
    guest: { label: 'Guest', color: 'hsl(48 96% 53%)' },
} satisfies ChartConfig;

interface UserChartsProps {
    graph: GraphBucket[];
    stats: Stats;
}

export function UserCharts({ graph, stats }: UserChartsProps) {
    const usersStats = (
        <div className="flex gap-4 text-sm">
            <div className="flex flex-col items-end gap-0.5">
                <span className="flex items-center gap-1 text-muted-foreground">
                    <span
                        className="inline-block h-3 w-1 rounded-sm"
                        style={{
                            backgroundColor:
                                usersChartConfig.authenticated_users.color,
                        }}
                    />
                    Users
                </span>
                <span className="font-medium tabular-nums">
                    {stats.authenticated_users.toLocaleString()}
                </span>
            </div>
        </div>
    );

    const requestsStats = (
        <div className="flex gap-4 text-sm">
            {(
                [
                    ['authenticated', 'Authenticated'],
                    ['guest', 'Guest'],
                ] as const
            ).map(([key, label]) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="flex items-center gap-1 text-muted-foreground">
                        <span
                            className="inline-block h-3 w-1 rounded-sm"
                            style={{
                                backgroundColor: requestsChartConfig[key].color,
                            }}
                        />
                        {label}
                    </span>
                    <span className="font-medium tabular-nums">
                        {stats[
                            key === 'authenticated'
                                ? 'authenticated_requests'
                                : 'guest_requests'
                        ].toLocaleString()}
                    </span>
                </div>
            ))}
        </div>
    );

    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ChartPanel
                config={usersChartConfig}
                title="Authenticated Users"
                heroValue={stats.authenticated_users.toLocaleString()}
                legendStats={usersStats}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <AreaChart
                        syncId="users"
                        data={graph}
                        margin={{ top: 0, right: 0, left: 0, bottom: 0 }}
                    >
                        <defs>
                            <linearGradient
                                id="fillAuthUsers"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="5%"
                                    stopColor={
                                        usersChartConfig.authenticated_users
                                            .color
                                    }
                                    stopOpacity={0.3}
                                />
                                <stop
                                    offset="95%"
                                    stopColor={
                                        usersChartConfig.authenticated_users
                                            .color
                                    }
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
                                            color: usersChartConfig
                                                .authenticated_users.color,
                                            label: 'Authenticated Users',
                                            value:
                                                (payload?.find(
                                                    (p) =>
                                                        p.dataKey ===
                                                        'authenticated_users',
                                                )?.value as number) ?? 0,
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
                            dataKey="authenticated_users"
                            stroke={usersChartConfig.authenticated_users.color}
                            strokeWidth={2}
                            fill="url(#fillAuthUsers)"
                            dot={isolatedDot(
                                graph,
                                'authenticated_users',
                                usersChartConfig.authenticated_users.color,
                            )}
                        />
                    </AreaChart>
                )}
            </ChartPanel>

            <ChartPanel
                config={requestsChartConfig}
                title="Requests"
                heroValue={(
                    stats.authenticated_requests + stats.guest_requests
                ).toLocaleString()}
                legendStats={requestsStats}
                firstBucket={graph[0]?.bucket}
                lastBucket={graph[graph.length - 1]?.bucket}
            >
                {(legendContent) => (
                    <BarChart
                        syncId="users"
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
                                            color: requestsChartConfig
                                                .authenticated.color,
                                            label: 'Authenticated',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey ===
                                                        'authenticated',
                                                )?.value ?? 0,
                                        },
                                        {
                                            color: requestsChartConfig.guest
                                                .color,
                                            label: 'Guest',
                                            value:
                                                payload?.find(
                                                    (p) =>
                                                        p.dataKey === 'guest',
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
                            dataKey="authenticated"
                            stackId="a"
                            fill={requestsChartConfig.authenticated.color}
                            radius={0}
                        />
                        <Bar
                            dataKey="guest"
                            stackId="a"
                            fill={requestsChartConfig.guest.color}
                            radius={[3, 3, 0, 0]}
                        />
                    </BarChart>
                )}
            </ChartPanel>
        </div>
    );
}
