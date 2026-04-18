import { Deferred, Head, usePage } from '@inertiajs/react';
import { DataTable } from '@/components/analytics/data-table';
import { CardSkeleton, TableSkeleton } from '@/components/analytics/skeletons';
import ExceptionCard from '@/components/exceptions/exception-card';
import type { ExceptionOccurrence } from '@/components/exceptions/types';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as exceptionsIndex } from '@/routes/analytics/exceptions';
import type { ExceptionGraphBucket, ExceptionStats, ExceptionSummary, Pagination } from './types';
import { ExceptionCharts } from '@/components/exceptions/exception-charts';
import { ExceptionDetailStats } from './partials/exception-detail-stats';

interface Props {
    summary?: ExceptionSummary;
    rows?: Array<Record<string, unknown>>;
    pagination?: Pagination | null;
    graph?: ExceptionGraphBucket[];
    stats?: ExceptionStats;
    period: string;
}

const occurrenceColumns = [
    { key: 'user', label: 'User' },
    { key: 'php_version', label: 'PHP' },
    { key: 'laravel_version', label: 'Laravel' },
    { key: 'recorded_at', label: 'Time' },
];

const topSectionSkeleton = (
    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <CardSkeleton />
        <CardSkeleton />
    </div>
);

function summaryToOccurrence(summary: ExceptionSummary): ExceptionOccurrence {
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

export default function ExceptionShow({ summary, rows, pagination, graph, stats, period }: Props) {
    const { props } = usePage();
    const { activeEnvironment } = props as {
        activeEnvironment?: { slug: string } | null;
    };

    const breadcrumbs = [
        {
            title: 'Exceptions',
            href: activeEnvironment
                ? exceptionsIndex.url({ environment: activeEnvironment.slug })
                : '#',
        },
        { title: summary?.message ?? '…', href: '#' },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats', 'summary']} fallback={topSectionSkeleton}>
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <ExceptionDetailStats summary={summary!} />
                    <ExceptionCharts graph={graph!} stats={stats!} />
                </div>
            </Deferred>
            <Deferred data={['summary']} fallback={<CardSkeleton />}>
                {summary && <ExceptionCard exception={summaryToOccurrence(summary)} />}
            </Deferred>
            <Deferred data={['rows']} fallback={<TableSkeleton />}>
                <section>
                    <h2 className="mb-2 text-sm font-medium">Occurrences</h2>
                    <DataTable columns={occurrenceColumns} rows={rows ?? []} pagination={pagination} />
                </section>
            </Deferred>
        </AnalyticsLayout>
    );
}
