import { Deferred, Head, usePage } from '@inertiajs/react';
import { InfoRow, Section } from '@/components/analytics/detail-card';
import SqlSyntaxHighlighter from '@/components/analytics/sql-syntax-highlighter';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { formatDuration } from '@/lib/utils';
import { index as queriesIndex } from '@/routes/analytics/queries';
import {
    QueryDetailCharts,
    type QueryDetailGraphBucket,
    type QueryDetailStats,
} from './partials/query-detail-charts';
import type { SortDir } from './types';

interface Props {
    graph?: QueryDetailGraphBucket[];
    stats?: QueryDetailStats;
    sql_normalized?: string | null;
    period: string;
    sort: string;
    direction: SortDir;
}

function ChartsSkeleton() {
    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {[0, 1].map((i) => (
                <div
                    key={i}
                    className="h-[206px] animate-pulse rounded-xl border bg-muted/40"
                />
            ))}
        </div>
    );
}

function CardSkeleton() {
    return <div className="h-48 animate-pulse rounded-xl border bg-muted/40" />;
}

export default function QueryShow({ graph, stats, sql_normalized, period }: Props) {
    const { props } = usePage();
    const { activeEnvironment } = props as {
        activeEnvironment?: { slug: string } | null;
    };

    const breadcrumbs = [
        {
            title: 'Queries',
            href: activeEnvironment
                ? queriesIndex.url({ environment: activeEnvironment.slug })
                : '#',
        },
        {
            title: sql_normalized ?? '…',
            href: '#',
        },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <QueryDetailCharts graph={graph!} stats={stats!} />
            </Deferred>

            <Deferred data={['stats']} fallback={<CardSkeleton />}>
                <Card className="gap-0 bg-surface py-0">
                    <CardHeader className="border-b py-4">
                        <span className="text-sm font-medium">Query</span>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-6 py-6 lg:grid-cols-2">
                        <Section title="Info">
                            <InfoRow
                                label="Calls"
                                value={stats?.count.toLocaleString() ?? '—'}
                            />
                            <InfoRow
                                label="Total Time"
                                value={formatDuration(stats?.total ?? null)}
                            />
                            <InfoRow
                                label="Avg Time"
                                value={formatDuration(stats?.avg ?? null)}
                            />
                            <InfoRow
                                label="P95"
                                value={formatDuration(stats?.p95 ?? null)}
                            />
                        </Section>
                        <div className="overflow-hidden rounded-lg border border-border bg-muted/30">
                            <SqlSyntaxHighlighter
                                className="p-4 text-xs"
                                wrapLongLines
                            >
                                {stats?.sql_normalized ?? ''}
                            </SqlSyntaxHighlighter>
                        </div>
                    </CardContent>
                </Card>
            </Deferred>
        </AnalyticsLayout>
    );
}
