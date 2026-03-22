import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
    timezone: string;
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

            <div className="max-w-3xl mx-auto px-4 py-8 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">{organization.name}</h1>
                    {organization.timezone && (
                        <p className="text-sm text-muted-foreground mt-1">{organization.timezone}</p>
                    )}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Link
                        href={`${baseUrl}/projects`}
                        className="block rounded-lg border bg-card p-5 hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-foreground">Applications</p>
                        <p className="text-sm text-muted-foreground mt-1">View and manage applications</p>
                    </Link>

                    <Link
                        href={`${baseUrl}/members`}
                        className="block rounded-lg border bg-card p-5 hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-foreground">Members</p>
                        <p className="text-sm text-muted-foreground mt-1">Manage team members</p>
                    </Link>

                    <Link
                        href={`${baseUrl}/edit`}
                        className="block rounded-lg border bg-card p-5 hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-foreground">Settings</p>
                        <p className="text-sm text-muted-foreground mt-1">Edit organization settings</p>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
