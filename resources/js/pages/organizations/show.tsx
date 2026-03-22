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
                        <h1 className="text-2xl font-semibold text-foreground">{organization.name}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{organization.slug}</p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={`${baseUrl}/edit`}>Edit</Link>
                    </Button>
                </div>

                {/* Details */}
                <div className="rounded-lg border bg-card divide-y divide-border">
                    <div className="px-6 py-4 flex items-center justify-between">
                        <span className="text-sm font-medium text-muted-foreground">Timezone</span>
                        <span className="text-sm text-foreground">{organization.timezone || '—'}</span>
                    </div>
                    <div className="px-6 py-4 flex items-center justify-between">
                        <span className="text-sm font-medium text-muted-foreground">Locale</span>
                        <span className="text-sm text-foreground">{organization.locale || '—'}</span>
                    </div>
                </div>

                {/* Navigation */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Link
                        href={`${baseUrl}/projects`}
                        className="block rounded-lg border bg-card p-5 hover:border  hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-foreground">Projects</p>
                        <p className="text-sm text-muted-foreground mt-1">View and manage projects</p>
                    </Link>

                    <Link
                        href={`${baseUrl}/members`}
                        className="block rounded-lg border bg-card p-5 hover:border  hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-foreground">Members</p>
                        <p className="text-sm text-muted-foreground mt-1">Manage team members</p>
                    </Link>

                    <Link
                        href={`${baseUrl}/edit`}
                        className="block rounded-lg border bg-card p-5 hover:border  hover:shadow-sm transition-all"
                    >
                        <p className="font-medium text-foreground">Settings</p>
                        <p className="text-sm text-muted-foreground mt-1">Edit organization settings</p>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
