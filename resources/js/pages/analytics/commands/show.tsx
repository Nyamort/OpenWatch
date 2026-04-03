import { Deferred, Head, usePage } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as commandsIndex } from '@/routes/analytics/commands';
import { CommandDetailCharts } from './partials/command-detail-charts';
import { CommandDetailTable } from './partials/command-detail-table';
import type {
    CommandDetailGraphBucket,
    CommandDetailSortKey,
    CommandDetailStats,
    CommandRunRow,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: CommandDetailGraphBucket[];
    stats?: CommandDetailStats;
    runs?: CommandRunRow[];
    pagination?: Pagination;
    name: string;
    period: string;
    sort: CommandDetailSortKey;
    direction: SortDir;
}

function ChartsSkeleton() {
    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {[0, 1].map((i) => (
                <div
                    key={i}
                    className="h-[206px] animate-pulse rounded-xl border bg-muted/40"
                />
            ))}
        </div>
    );
}

function TableSkeleton() {
    return (
        <div className="flex flex-col gap-1.5">
            <div className="h-11 animate-pulse rounded-lg bg-muted/40" />
            {Array.from({ length: 8 }).map((_, i) => (
                <div
                    key={i}
                    className="h-11 animate-pulse rounded-lg bg-muted/20"
                />
            ))}
        </div>
    );
}

export default function CommandShow({
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
                <CommandDetailCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['runs', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <CommandDetailTable
                    runs={runs!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
