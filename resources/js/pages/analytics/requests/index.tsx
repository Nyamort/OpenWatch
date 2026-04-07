import { Deferred, Head } from '@inertiajs/react';
import { ChartsSkeleton, TableWithSearchSkeleton } from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { RequestCharts } from './partials/request-charts';
import { RequestPathsTable } from './partials/request-paths-table';
import type {
    GraphBucket,
    Pagination,
    PathRow,
    SortDir,
    SortKey,
    Stats,
} from './types';

interface Props {
    graph?: GraphBucket[];
    stats?: Stats;
    paths?: PathRow[];
    pagination?: Pagination;
    period: string;
    sort: SortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Requests', href: '#' }];

export default function RequestsIndex({
    graph,
    stats,
    paths,
    pagination,
    period,
    sort,
    direction,
    search,
}: Props) {
    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <RequestCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['paths', 'pagination']}
                fallback={<TableWithSearchSkeleton />}
            >
                <RequestPathsTable
                    paths={paths!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
