import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
    timezone: string;
    locale: string;
}

interface Props {
    organizations: Organization[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Organizations', href: '/organizations' }];

export default function OrganizationsIndex({ organizations }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organizations" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold text-foreground">Organizations</h1>
                    <Button asChild>
                        <Link href="/organizations/create">Create Organization</Link>
                    </Button>
                </div>

                {organizations.length === 0 ? (
                    <div className="rounded-lg border border-dashed border  p-12 text-center">
                        <p className="text-muted-foreground">No organizations yet.</p>
                        <p className="text-sm text-muted-foreground mt-1">Create one to get started.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        {organizations.map((org) => (
                            <Link
                                key={org.id}
                                href={`/organizations/${org.slug}`}
                                className="block rounded-lg border bg-card p-5 hover:border  hover:shadow-sm transition-all"
                            >
                                <div className="flex items-center gap-3">
                                    {org.logo_url ? (
                                        <img
                                            src={org.logo_url}
                                            alt={org.name}
                                            className="h-10 w-10 rounded-md object-cover"
                                        />
                                    ) : (
                                        <div className="h-10 w-10 rounded-md bg-muted flex items-center justify-center text-muted-foreground font-semibold text-lg">
                                            {org.name.charAt(0).toUpperCase()}
                                        </div>
                                    )}
                                    <div className="min-w-0">
                                        <p className="font-medium text-foreground truncate">{org.name}</p>
                                        <p className="text-sm text-muted-foreground truncate">{org.slug}</p>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
