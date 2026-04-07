import { Deferred, Head } from '@inertiajs/react';
import { ChartsSkeleton, TableWithSearchSkeleton } from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { OutgoingRequestCharts } from './partials/outgoing-request-charts';
import { OutgoingRequestTable } from './partials/outgoing-request-table';
import type {
    OutgoingRequestGraphBucket,
    OutgoingRequestHostRow,
    OutgoingRequestSortKey,
    OutgoingRequestStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: OutgoingRequestGraphBucket[];
    stats?: OutgoingRequestStats;
    hosts?: OutgoingRequestHostRow[];
    pagination?: Pagination;
    period: string;
    sort: OutgoingRequestSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Outgoing Requests', href: '#' }];

export default function OutgoingRequestsIndex({
    graph,
    stats,
    hosts,
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
                <OutgoingRequestCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['hosts', 'pagination']}
                fallback={<TableWithSearchSkeleton />}
            >
                <OutgoingRequestTable
                    hosts={hosts!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
