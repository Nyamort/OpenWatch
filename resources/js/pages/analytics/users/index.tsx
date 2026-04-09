import { Deferred, Head } from '@inertiajs/react';
import {
    ChartsSkeleton,
    TableWithSearchSkeleton,
} from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { UserCharts } from './partials/user-charts';
import { UserTable } from './partials/user-table';
import type {
    GraphBucket,
    Pagination,
    SortDir,
    SortKey,
    Stats,
    UserRow,
} from './types';

interface Props {
    graph?: GraphBucket[];
    stats?: Stats;
    users?: UserRow[];
    pagination?: Pagination;
    period: string;
    sort: SortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Users', href: '#' }];

export default function UsersIndex({
    graph,
    stats,
    users,
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
                <UserCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['users', 'pagination']}
                fallback={<TableWithSearchSkeleton />}
            >
                <UserTable
                    users={users!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
