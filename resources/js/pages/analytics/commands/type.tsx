import { Deferred, Head, usePage } from '@inertiajs/react';
import {
    ChartsSkeleton,
    TableSkeleton,
} from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as commandsIndex } from '@/routes/analytics/commands';
import { CommandTypeCharts } from './partials/command-type-charts';
import { CommandTypeTable } from './partials/command-type-table';
import type {
    CommandTypeGraphBucket,
    CommandTypeSortKey,
    CommandTypeStats,
    CommandRunRow,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: CommandTypeGraphBucket[];
    stats?: CommandTypeStats;
    runs?: CommandRunRow[];
    pagination?: Pagination;
    name: string;
    period: string;
    sort: CommandTypeSortKey;
    direction: SortDir;
}

export default function CommandType({
    graph,
    stats,
    runs,
    pagination,
    name,
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
            title: 'Commands',
            href:
                activeOrganization && activeProject && activeEnvironment
                    ? commandsIndex.url({ environment: activeEnvironment.slug })
                    : '#',
        },
        { title: name || 'Unknown Command', href: '#' },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <CommandTypeCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['runs', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <CommandTypeTable
                    runs={runs!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    environment={activeEnvironment?.slug ?? ''}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
