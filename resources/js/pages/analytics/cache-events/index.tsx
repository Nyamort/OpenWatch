import { Deferred, Head } from '@inertiajs/react';
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

function TableSkeleton() {
    return (
        <div className="flex flex-col gap-3">
            <div className="h-10 w-64 animate-pulse rounded-lg bg-muted/40" />
            <div className="flex flex-col gap-1.5">
                <div className="h-11 animate-pulse rounded-lg bg-muted/40" />
                {Array.from({ length: 8 }).map((_, i) => (
                    <div
                        key={i}
                        className="h-11 animate-pulse rounded-lg bg-muted/20"
                    />
                ))}
            </div>
        </div>
    );
}

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
                fallback={<TableSkeleton />}
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
