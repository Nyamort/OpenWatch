import { Deferred, Head } from '@inertiajs/react';
import { ChartsSkeleton, TableWithSearchSkeleton } from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { CommandCharts } from './partials/command-charts';
import { CommandTable } from './partials/command-table';
import type {
    CommandGraphBucket,
    CommandRow,
    CommandSortKey,
    CommandStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: CommandGraphBucket[];
    stats?: CommandStats;
    commands?: CommandRow[];
    pagination?: Pagination;
    period: string;
    sort: CommandSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Commands', href: '#' }];

export default function CommandsIndex({
    graph,
    stats,
    commands,
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
                <CommandCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['commands', 'pagination']}
                fallback={<TableWithSearchSkeleton />}
            >
                <CommandTable
                    commands={commands!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
