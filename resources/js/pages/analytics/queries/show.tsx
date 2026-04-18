import { Deferred, Head, usePage } from '@inertiajs/react';
import { format as formatSql } from 'sql-formatter';
import { InfoRow, Section } from '@/components/analytics/detail-card';
import { CardSkeleton, ChartsSkeleton } from '@/components/analytics/skeletons';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { CodeBlock } from '@/components/ui/code-block';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { formatDuration } from '@/lib/utils';
import { index as queriesIndex } from '@/routes/analytics/queries';
import {
    QueryDetailCharts,
    type QueryDetailGraphBucket,
    type QueryDetailStats,
} from './partials/query-detail-charts';
import { QueryRunTable, type QueryRunRow } from './partials/query-run-table';
import type { Pagination, SortDir } from './types';

interface Props {
    graph?: QueryDetailGraphBucket[];
    stats?: QueryDetailStats;
    runs?: QueryRunRow[];
    pagination?: Pagination;
    sql_normalized?: string | null;
    period: string;
    sort: string;
    direction: SortDir;
}

function SqlBlock({ sql }: { sql: string }) {
    return (
        <CodeBlock
            code={formatSql(sql, { language: 'sql' })}
            language="sql"
            copyable
            className="overflow-hidden rounded-lg border border-border bg-muted/30 p-4"
        />
    );
}

export default function QueryShow({
    graph,
    stats,
    runs,
    pagination,
    sql_normalized,
    period,
    sort,
    direction,
}: Props) {
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
            title: sql_normalized
                ? sql_normalized.slice(0, 20).trimEnd() +
                  (sql_normalized.length > 20 ? '…' : '')
                : '…',
            href: '#',
        },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <QueryDetailCharts graph={graph!} stats={stats!} />
            </Deferred>

            <Deferred
                data={['stats', 'runs', 'pagination']}
                fallback={<CardSkeleton />}
            >
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
                        <SqlBlock sql={stats?.sql_normalized ?? ''} />
                    </CardContent>
                </Card>

                {runs && pagination && (
                    <QueryRunTable
                        runs={runs}
                        pagination={pagination}
                        sort={sort}
                        direction={direction}
                        count={stats?.count ?? 0}
                    />
                )}
            </Deferred>
        </AnalyticsLayout>
    );
}
