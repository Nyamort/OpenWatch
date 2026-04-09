import { Deferred, Head, usePage } from '@inertiajs/react';
import {
    ChartsSkeleton,
    TableSkeleton,
} from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as outgoingRequestsIndex } from '@/routes/analytics/outgoing-requests';
import { OutgoingRequestHostCharts } from './partials/outgoing-request-host-charts';
import { OutgoingRequestHostTable } from './partials/outgoing-request-host-table';
import type {
    OutgoingRequestGraphBucket,
    OutgoingRequestHostSortKey,
    OutgoingRequestRunRow,
    OutgoingRequestStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: OutgoingRequestGraphBucket[];
    stats?: OutgoingRequestStats;
    runs?: OutgoingRequestRunRow[];
    pagination?: Pagination;
    host: string;
    period: string;
    sort: OutgoingRequestHostSortKey;
    direction: SortDir;
}

export default function OutgoingRequestHost({
    graph,
    stats,
    runs,
    pagination,
    host,
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
            title: 'Outgoing Requests',
            href: activeEnvironment
                ? outgoingRequestsIndex.url({
                      environment: activeEnvironment.slug,
                  })
                : '#',
        },
        { title: host || '…', href: '#' },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <OutgoingRequestHostCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['runs', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <OutgoingRequestHostTable
                    runs={runs!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    count={stats?.total ?? 0}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
