import { Deferred, Head } from '@inertiajs/react';
import { ChartsSkeleton, TableWithSearchSkeleton } from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { CacheCharts } from './partials/cache-charts';
import { CacheTable } from './partials/cache-table';
import type {
    CacheEventsGraphBucket,
    CacheFailuresGraphBucket,
    CacheKeyRow,
    CacheSortKey,
    CacheStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    events_graph?: CacheEventsGraphBucket[];
    failures_graph?: CacheFailuresGraphBucket[];
    stats?: CacheStats;
    keys?: CacheKeyRow[];
    pagination?: Pagination;
    period: string;
    sort: CacheSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Cache', href: '#' }];

export default function CacheEventsIndex({
    events_graph,
    failures_graph,
    stats,
    keys,
    pagination,
    period,
    sort,
    direction,
    search,
}: Props) {
    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred
                data={['events_graph', 'failures_graph', 'stats']}
                fallback={<ChartsSkeleton />}
            >
                <CacheCharts
                    eventsGraph={events_graph!}
                    failuresGraph={failures_graph!}
                    stats={stats!}
                />
            </Deferred>
            <Deferred
                data={['keys', 'pagination']}
                fallback={<TableWithSearchSkeleton />}
            >
                <CacheTable
                    keys={keys!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
