import { Deferred, Head, usePage } from '@inertiajs/react';
import { ChartsSkeleton, TableSkeleton } from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as mailIndex } from '@/routes/analytics/mail';
import { MailTypeCharts } from './partials/mail-type-charts';
import { MailTypeTable } from './partials/mail-type-table';
import type {
    MailRunRow,
    MailTypeGraphBucket,
    MailTypeSortKey,
    MailTypeStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: MailTypeGraphBucket[];
    stats?: MailTypeStats;
    runs?: MailRunRow[];
    pagination?: Pagination;
    mailClass: string;
    period: string;
    sort: MailTypeSortKey;
    direction: SortDir;
}

export default function MailType({
    graph,
    stats,
    runs,
    pagination,
    mailClass,
    period,
    sort,
    direction,
}: Props) {
    const { props } = usePage();
    const { activeEnvironment } = props as {
        activeEnvironment?: { slug: string } | null;
    };

    const shortName = mailClass
        ? mailClass.split('\\').pop() ?? mailClass
        : '…';

    const breadcrumbs = [
        {
            title: 'Mail',
            href: activeEnvironment
                ? mailIndex.url({ environment: activeEnvironment.slug })
                : '#',
        },
        { title: shortName, href: '#' },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <MailTypeCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred data={['runs', 'pagination']} fallback={<TableSkeleton />}>
                <MailTypeTable
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
