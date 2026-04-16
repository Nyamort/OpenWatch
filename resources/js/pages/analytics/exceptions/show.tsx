import { Deferred, Head } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import {
    BarCursor,
    ChartPanel,
    tooltipProps,
} from '@/components/analytics/chart-panel';
import { AnalyticsTooltip } from '@/components/analytics/chart-tooltip';
import { DataTable } from '@/components/analytics/data-table';
import { CardSkeleton, TableSkeleton } from '@/components/analytics/skeletons';
import ExceptionCard from '@/components/exceptions/exception-card';
import type { ExceptionOccurrence } from '@/components/exceptions/types';
import {
    ChartLegend,
    ChartTooltip,
    type ChartConfig,
} from '@/components/ui/chart';
import AnalyticsLayout from '@/layouts/analytics-layout';
import type { ExceptionGraphBucket, ExceptionStats, Pagination } from './types';

interface Summary {
    group_key: string;
    recorded_at: string;
    class: string;
    message: string;
    file: string;
    line: number;
    handled: boolean | number;
    code: string | null;
    php_version: string | null;
    laravel_version: string | null;
    trace: string;
    last_seen: string | null;
    first_seen: string | null;
    first_reported_in: string | null;
    impacted_users: number;
    occurrences: number;
    servers: number;
    [key: string]: unknown;
}

function summaryToOccurrence(summary: Summary): ExceptionOccurrence {
    let trace: ExceptionOccurrence['trace'] = [];
    try {
        trace = JSON.parse(summary.trace);
    } catch {
        // malformed trace — leave empty
    }

    return {
        group: summary.group_key,
        timestamp: summary.recorded_at,
        file: summary.file,
        line: summary.line,
        class: summary.class,
        message: summary.message,
        handled: Boolean(summary.handled),
        code: summary.code ?? '0',
        php_version: summary.php_version ?? '',
        laravel_version: summary.laravel_version ?? '',
        trace,
    };
}

interface Props {
    summary?: Summary;
    rows?: Array<Record<string, unknown>>;
    pagination?: Pagination | null;
    graph?: ExceptionGraphBucket[];
    stats?: ExceptionStats;
    period: string;
}

const chartConfig = {
    handled: { label: 'Handled', color: 'oklch(0.50 0 0)' },
    unhandled: { label: 'Unhandled', color: 'hsl(0 72% 51%)' },
} satisfies ChartConfig;

const occurrenceColumns = [
    { key: 'user', label: 'User' },
    { key: 'php_version', label: 'PHP' },
    { key: 'laravel_version', label: 'Laravel' },
    { key: 'recorded_at', label: 'Time' },
];

function formatDatetime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString([], {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function StatItem({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex flex-col gap-1">
            <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </span>
            <span className="font-semibold tabular-nums">{value ?? '—'}</span>
        </div>
    );
}

function ExceptionDetailChart({
    graph,
    stats,
}: {
    graph: ExceptionGraphBucket[];
    stats: ExceptionStats;
}) {
    const legendStats = (
        <div className="flex gap-4 text-sm">
            {(['handled', 'unhandled'] as const).map((key) => (
                <div key={key} className="flex flex-col items-end gap-0.5">
                    <span className="flex items-center gap-1 text-muted-foreground">
                        <span
                            className="inline-block h-3 w-1 rounded-sm"
                            style={{ backgroundColor: chartConfig[key].color }}
                        />
                        {chartConfig[key].label}
                    </span>
                    <span className="font-medium tabular-nums">
                        {stats[key].toLocaleString()}
                    </span>
                </div>
            ))}
        </div>
    );

    return (
        <ChartPanel
            config={chartConfig}
            title="Occurrences"
            heroValue={stats.count.toLocaleString()}
            legendStats={legendStats}
            firstBucket={graph[0]?.bucket}
            lastBucket={graph[graph.length - 1]?.bucket}
        >
            {(legendContent) => (
                <BarChart
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
                                        color: chartConfig.handled.color,
                                        label: 'Handled',
                                        value:
                                            payload?.find(
                                                (p) => p.dataKey === 'handled',
                                            )?.value ?? 0,
                                    },
                                    {
                                        color: chartConfig.unhandled.color,
                                        label: 'Unhandled',
                                        value:
                                            payload?.find(
                                                (p) =>
                                                    p.dataKey === 'unhandled',
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
                                                    ((p.value as number) ?? 0),
                                                0,
                                            ) ?? 0}
                                        </span>
                                    </div>
                                }
                            />
                        )}
                    />
                    <ChartLegend verticalAlign="top" content={legendContent} />
                    <Bar
                        dataKey="handled"
                        stackId="a"
                        fill={chartConfig.handled.color}
                        radius={0}
                    />
                    <Bar
                        dataKey="unhandled"
                        stackId="a"
                        fill={chartConfig.unhandled.color}
                        radius={[3, 3, 0, 0]}
                    />
                </BarChart>
            )}
        </ChartPanel>
    );
}

function ExceptionDetailStats({ summary }: { summary: Summary }) {
    return (
        <div className="flex flex-col justify-between gap-6 rounded-xl border bg-surface p-5">
            <div className="flex flex-col gap-1">
                <p className="font-mono text-xs text-muted-foreground">
                    {summary.file}:{summary.line}
                </p>
                <p className="font-medium">{summary.message}</p>
            </div>
            <div className="grid grid-cols-2 gap-x-6 gap-y-4">
                <StatItem
                    label="Last Seen"
                    value={formatDatetime(summary.last_seen)}
                />
                <StatItem
                    label="First Seen"
                    value={formatDatetime(summary.first_seen)}
                />
                <StatItem
                    label="First Reported In"
                    value={summary.first_reported_in ?? '—'}
                />
                <StatItem
                    label="Impacted Users"
                    value={summary.impacted_users.toLocaleString()}
                />
                <StatItem
                    label="Occurrences"
                    value={summary.occurrences.toLocaleString()}
                />
                <StatItem
                    label="Servers"
                    value={summary.servers.toLocaleString()}
                />
            </div>
        </div>
    );
}

const topSectionSkeleton = (
    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <CardSkeleton />
        <CardSkeleton />
    </div>
);

export default function ExceptionShow({
    summary,
    rows,
    pagination,
    graph,
    stats,
    period,
}: Props) {
    return (
        <AnalyticsLayout period={period}>
            <Head />
            <Deferred
                data={['graph', 'stats', 'summary']}
                fallback={topSectionSkeleton}
            >
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <ExceptionDetailChart graph={graph!} stats={stats!} />
                    <ExceptionDetailStats summary={summary!} />
                </div>
            </Deferred>
            <Deferred data={['rows']} fallback={<TableSkeleton />}>
                <section>
                    <h2 className="mb-2 text-sm font-medium">Occurrences</h2>
                    <DataTable
                        columns={occurrenceColumns}
                        rows={rows ?? []}
                        pagination={pagination}
                    />
                </section>
            </Deferred>
            <Deferred data={['summary']} fallback={<CardSkeleton />}>
                <section>
                    <h2 className="mb-2 text-sm font-medium">
                        Latest Occurrence
                    </h2>
                    <ExceptionCard exception={summaryToOccurrence(summary!)} />
                </section>
            </Deferred>
        </AnalyticsLayout>
    );
}
