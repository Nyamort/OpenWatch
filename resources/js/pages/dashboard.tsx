import { Deferred, Head, Link } from '@inertiajs/react';
import { BriefcaseBusiness, Globe, Users } from 'lucide-react';
import { ChartsSkeleton } from '@/components/analytics/skeletons';
import { Button } from '@/components/ui/button';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { JobCharts } from '@/pages/analytics/jobs/partials/job-charts';
import type {
    JobGraphBucket,
    JobStats,
} from '@/pages/analytics/jobs/types';
import { RequestCharts } from '@/pages/analytics/requests/partials/request-charts';
import type {
    GraphBucket as RequestGraphBucket,
    Stats as RequestStats,
} from '@/pages/analytics/requests/types';
import { UserCharts } from '@/pages/analytics/users/partials/user-charts';
import type {
    GraphBucket as UserGraphBucket,
    Stats as UserStats,
} from '@/pages/analytics/users/types';
import { index as jobsIndex } from '@/routes/analytics/jobs';
import { index as requestsIndex } from '@/routes/analytics/requests';
import { index as usersIndex } from '@/routes/analytics/users';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

interface Props {
    hasContext: boolean;
    period: string;
    context?: { org: string; project: string; env: string };
    requestGraph?: RequestGraphBucket[];
    requestStats?: RequestStats;
    jobGraph?: JobGraphBucket[];
    jobStats?: JobStats;
    userGraph?: UserGraphBucket[];
    userStats?: UserStats;
}

export default function Dashboard({
    hasContext,
    period,
    context,
    requestGraph,
    requestStats,
    jobGraph,
    jobStats,
    userGraph,
    userStats,
}: Props) {
    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            {hasContext && context ? (
                <>
                    <section className="flex flex-col gap-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-base font-semibold text-foreground">
                                Activity
                            </h2>
                            <Button asChild size="sm" variant="outline">
                                <Link href={requestsIndex.url(context.env)}>
                                    <Globe />
                                    Requests
                                </Link>
                            </Button>
                        </div>
                        <Deferred
                            data={['requestGraph', 'requestStats']}
                            fallback={<ChartsSkeleton />}
                        >
                            <RequestCharts
                                graph={requestGraph!}
                                stats={requestStats!}
                            />
                        </Deferred>
                    </section>

                    <section className="flex flex-col gap-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-base font-semibold text-foreground">
                                Application
                            </h2>
                            <Button asChild size="sm" variant="outline">
                                <Link href={jobsIndex.url(context.env)}>
                                    <BriefcaseBusiness />
                                    Jobs
                                </Link>
                            </Button>
                        </div>
                        <Deferred
                            data={['jobGraph', 'jobStats']}
                            fallback={<ChartsSkeleton />}
                        >
                            <JobCharts
                                graph={jobGraph!}
                                stats={jobStats!}
                            />
                        </Deferred>
                    </section>

                    <section className="flex flex-col gap-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-base font-semibold text-foreground">
                                Users
                            </h2>
                            <Button asChild size="sm" variant="outline">
                                <Link href={usersIndex.url(context.env)}>
                                    <Users />
                                    Users
                                </Link>
                            </Button>
                        </div>
                        <Deferred
                            data={['userGraph', 'userStats']}
                            fallback={<ChartsSkeleton />}
                        >
                            <UserCharts
                                graph={userGraph!}
                                stats={userStats!}
                            />
                        </Deferred>
                    </section>
                </>
            ) : (
                <div className="py-20 text-center text-muted-foreground">
                    <p className="text-lg">No project configured yet.</p>
                </div>
            )}
        </AnalyticsLayout>
    );
}
