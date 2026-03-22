import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
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
    description: string | null;
    health_status: string | null;
}

interface Props {
    organization: Organization;
    project: Project;
}

function healthStatusVariant(status: string | null): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'healthy') {
        return 'secondary';
    }
    if (status === 'degraded') {
        return 'default';
    }
    if (status === 'down') {
        return 'destructive';
    }
    return 'outline';
}

export default function ProjectsShow({ organization, project }: Props) {
    const orgUrl = `/organizations/${organization.slug}`;
    const projectUrl = `${orgUrl}/projects/${project.slug}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: organization.name, href: orgUrl },
        { title: 'Projects', href: `${orgUrl}/projects` },
        { title: project.name, href: projectUrl },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={project.name} />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">{project.name}</h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{project.slug}</p>
                        {project.description && (
                            <p className="text-sm text-gray-600 dark:text-gray-300 mt-2">{project.description}</p>
                        )}
                    </div>
                    {project.health_status && (
                        <Badge variant={healthStatusVariant(project.health_status)} className="capitalize">
                            {project.health_status}
                        </Badge>
                    )}
                </div>

                {/* Navigation links */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        href={`${projectUrl}/environments`}
                        className="block rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-gray-900 dark:text-white">Environments</p>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage deployment environments</p>
                    </Link>

                    <Link
                        href={`${projectUrl}/environments`}
                        className="block rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-gray-900 dark:text-white">Analytics</p>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">View metrics and performance</p>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
