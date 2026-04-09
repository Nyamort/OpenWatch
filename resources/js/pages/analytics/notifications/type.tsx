import { Deferred, Head, usePage } from '@inertiajs/react';
import {
    ChartsSkeleton,
    TableSkeleton,
} from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as notificationsIndex } from '@/routes/analytics/notifications';
import { NotificationTypeCharts } from './partials/notification-type-charts';
import { NotificationTypeTable } from './partials/notification-type-table';
import type {
    NotificationTypeGraphBucket,
    NotificationTypeSortKey,
    NotificationTypeStats,
    NotificationRunRow,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: NotificationTypeGraphBucket[];
    stats?: NotificationTypeStats;
    runs?: NotificationRunRow[];
    pagination?: Pagination;
    notificationClass: string;
    period: string;
    sort: NotificationTypeSortKey;
    direction: SortDir;
}

export default function NotificationShow({
    graph,
    stats,
    runs,
    pagination,
    notificationClass,
    period,
    sort,
    direction,
}: Props) {
    const { props } = usePage();
    const { activeEnvironment } = props as {
        activeEnvironment?: { slug: string } | null;
    };

    const shortName = notificationClass
        ? (notificationClass.split('\\').pop() ?? notificationClass)
        : '…';

    const breadcrumbs = [
        {
            title: 'Notifications',
            href: activeEnvironment
                ? notificationsIndex.url({
                      environment: activeEnvironment.slug,
                  })
                : '#',
        },
        { title: shortName, href: '#' },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <NotificationTypeCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['runs', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <NotificationTypeTable
                    runs={runs!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    count={stats?.count ?? 0}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
