import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { QueryCharts } from './partials/query-charts';
import { QueryTable } from './partials/query-table';
import type {
    Pagination,
    QueryGraphBucket,
    QueryRow,
    QuerySortKey,
    QueryStats,
    SortDir,
} from './types';

interface Props {
    graph: QueryGraphBucket[];
    stats: QueryStats;
    queries: QueryRow[];
    pagination: Pagination;
    period: string;
    sort: QuerySortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Queries', href: '#' }];

export default function QueriesIndex({
    graph,
    stats,
    queries,
    pagination,
    period,
    sort,
    direction,
    search,
}: Props) {
    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <QueryCharts graph={graph} stats={stats} />
            <QueryTable
                queries={queries}
                pagination={pagination}
                sort={sort}
                direction={direction}
                search={search}
            />
        </AnalyticsLayout>
    );
}
