import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface Project {
    id: number;
    name: string;
    slug: string;
}

interface Environment {
    id: number;
    name: string;
    slug: string;
    type: string;
    status: string;
}

interface Props {
    organization: Organization;
    project: Project;
    environment: Environment;
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'active') {
        return 'secondary';
    }
    if (status === 'inactive') {
        return 'outline';
    }
    return 'outline';
}

export default function EnvironmentsShow({ organization, project, environment }: Props) {
    const orgUrl = `/organizations/${organization.slug}`;
    const projectUrl = `${orgUrl}/projects/${project.slug}`;
    const environmentsUrl = `${projectUrl}/environments`;
    const environmentUrl = `${environmentsUrl}/${environment.slug}`;
    const tokensUrl = `${environmentUrl}/tokens`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: organization.name, href: orgUrl },
        { title: project.name, href: projectUrl },
        { title: 'Environments', href: environmentsUrl },
        { title: environment.name, href: environmentUrl },
    ];

    function rotateToken() {
        router.post(tokensUrl, {}, { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={environment.name} />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">{environment.name}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{environment.slug}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge variant="outline" className="capitalize">{environment.type}</Badge>
                        <Badge variant={statusVariant(environment.status)} className="capitalize">{environment.status}</Badge>
                    </div>
                </div>

                {/* Navigation links */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        href={`${environmentUrl}/issues`}
                        className="block rounded-lg border bg-card p-5 hover:border  hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-foreground">Issues</p>
                        <p className="text-sm text-muted-foreground mt-1">View errors and exceptions</p>
                    </Link>

                    <Link
                        href={`${environmentUrl}/alert-rules`}
                        className="block rounded-lg border bg-card p-5 hover:border  hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-foreground">Alert Rules</p>
                        <p className="text-sm text-muted-foreground mt-1">Configure threshold alerts</p>
                    </Link>
                </div>

                {/* Token management */}
                <div className="rounded-lg border bg-card">
                    <div className="px-6 py-4 border-b border ">
                        <h2 className="text-base font-semibold text-foreground">API Token</h2>
                        <p className="text-sm text-muted-foreground mt-0.5">
                            Use this token to send data from your application.
                        </p>
                    </div>
                    <div className="px-6 py-4 flex items-center gap-3">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={rotateToken}
                        >
                            Rotate Token
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            Rotating will invalidate the existing token immediately.
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
