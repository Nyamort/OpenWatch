import { Deferred, Head, usePage } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
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

export default function QueryShow({ graph, stats, period }: Props) {
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
        { title: 'Query detail', href: '#' },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <QueryDetailCharts graph={graph!} stats={stats!} />
            </Deferred>
        </AnalyticsLayout>
    );
}
