import { Deferred, Head, usePage } from '@inertiajs/react';
import {
    ChartsSkeleton,
    TableSkeleton,
} from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as requestsIndex } from '@/routes/analytics/requests';
import { RouteCharts } from './partials/route-charts';
import { RouteTable } from './partials/route-table';
import type {
    GraphBucket,
    Pagination,
    RouteRequestRow,
    RouteSortKey,
    SortDir,
    Stats,
} from './types';

interface Props {
    graph?: GraphBucket[];
    stats?: Stats;
    requests?: RouteRequestRow[];
    pagination?: Pagination;
    route_path: string;
    method: string | null;
    period: string;
    sort: RouteSortKey;
    direction: SortDir;
}

export default function RequestsRoute({
    graph,
    stats,
    requests,
    pagination,
    route_path,
    period,
    sort,
    direction,
}: Props) {
    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as {
        activeOrganization?: { slug: string } | null;
        activeProject?: { slug: string } | null;
        activeEnvironment?: { slug: string } | null;
    };

    const breadcrumbs = [
        {
            title: 'Requests',
            href:
                activeOrganization && activeProject && activeEnvironment
                    ? requestsIndex.url({ environment: activeEnvironment.slug })
                    : '#',
        },
        { title: route_path || 'Unmatched Route', href: '#' },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <RouteCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['requests', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <RouteTable
                    requests={requests!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    environment={activeEnvironment?.slug ?? ''}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
