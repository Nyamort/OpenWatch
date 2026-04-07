import { Deferred, Head } from '@inertiajs/react';
import { ChartsSkeleton, TableWithSearchSkeleton } from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { NotificationCharts } from './partials/notification-charts';
import { NotificationTable } from './partials/notification-table';
import type {
    NotificationGraphBucket,
    NotificationRow,
    NotificationSortKey,
    NotificationStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: NotificationGraphBucket[];
    stats?: NotificationStats;
    notifications?: NotificationRow[];
    pagination?: Pagination;
    period: string;
    sort: NotificationSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Notifications', href: '#' }];

export default function NotificationsIndex({
    graph,
    stats,
    notifications,
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
                <NotificationCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['notifications', 'pagination']}
                fallback={<TableWithSearchSkeleton />}
            >
                <NotificationTable
                    notifications={notifications!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
