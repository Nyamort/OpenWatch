import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
    timezone: string;
    locale: string;
}

interface Props {
    organization: Organization;
}

export default function OrganizationsShow({ organization }: Props) {
    const baseUrl = `/organizations/${organization.slug}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Organizations', href: '/organizations' },
        { title: organization.name, href: baseUrl },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={organization.name} />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">{organization.name}</h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{organization.slug}</p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={`${baseUrl}/edit`}>Edit</Link>
                    </Button>
                </div>

                {/* Details */}
                <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                    <div className="px-6 py-4 flex items-center justify-between">
                        <span className="text-sm font-medium text-gray-500 dark:text-gray-400">Timezone</span>
                        <span className="text-sm text-gray-900 dark:text-white">{organization.timezone || '—'}</span>
                    </div>
                    <div className="px-6 py-4 flex items-center justify-between">
                        <span className="text-sm font-medium text-gray-500 dark:text-gray-400">Locale</span>
                        <span className="text-sm text-gray-900 dark:text-white">{organization.locale || '—'}</span>
                    </div>
                </div>

                {/* Navigation */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Link
                        href={`${baseUrl}/projects`}
                        className="block rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-gray-900 dark:text-white">Projects</p>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">View and manage projects</p>
                    </Link>

                    <Link
                        href={`${baseUrl}/members`}
                        className="block rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-gray-900 dark:text-white">Members</p>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage team members</p>
                    </Link>

                    <Link
                        href={`${baseUrl}/edit`}
                        className="block rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-gray-900 dark:text-white">Settings</p>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Edit organization settings</p>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
