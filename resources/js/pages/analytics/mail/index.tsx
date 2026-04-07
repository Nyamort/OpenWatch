import { Deferred, Head } from '@inertiajs/react';
import { ChartsSkeleton, TableWithSearchSkeleton } from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { MailCharts } from './partials/mail-charts';
import { MailTable } from './partials/mail-table';
import type {
    MailGraphBucket,
    MailRow,
    MailSortKey,
    MailStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: MailGraphBucket[];
    stats?: MailStats;
    mails?: MailRow[];
    pagination?: Pagination;
    period: string;
    sort: MailSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Mails', href: '#' }];

export default function MailIndex({
    graph,
    stats,
    mails,
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
                <MailCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['mails', 'pagination']}
                fallback={<TableWithSearchSkeleton />}
            >
                <MailTable
                    mails={mails!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
