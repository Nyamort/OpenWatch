import { Deferred, Head, Link } from '@inertiajs/react';
import { Globe } from 'lucide-react';
import { ChartsSkeleton } from '@/components/analytics/skeletons';
import { Button } from '@/components/ui/button';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { RequestCharts } from '@/pages/analytics/requests/partials/request-charts';
import type {
    GraphBucket,
    Stats,
} from '@/pages/analytics/requests/types';
import { index as requestsIndex } from '@/routes/analytics/requests';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

interface Props {
    hasContext: boolean;
    period: string;
    context?: { org: string; project: string; env: string };
    graph?: GraphBucket[];
    stats?: Stats;
}

export default function Dashboard({
    hasContext,
    period,
    context,
    graph,
    stats,
}: Props) {
    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            {hasContext ? (
                <section className="flex flex-col gap-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-semibold text-foreground">
                            Activity
                        </h2>
                        {context && (
                            <Button asChild size="sm" variant="outline">
                                <Link href={requestsIndex.url(context.env)}>
                                    <Globe />
                                    Requests
                                </Link>
                            </Button>
                        )}
                    </div>
                    <Deferred
                        data={['graph', 'stats']}
                        fallback={<ChartsSkeleton />}
                    >
                        <RequestCharts graph={graph!} stats={stats!} />
                    </Deferred>
                </section>
            ) : (
                <div className="py-20 text-center text-muted-foreground">
                    <p className="text-lg">No project configured yet.</p>
                </div>
            )}
        </AnalyticsLayout>
    );
}
