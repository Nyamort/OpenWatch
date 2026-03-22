import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
}

interface Props {
    user: User;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Account', href: '/settings/account' },
];

export default function Account({ user }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Account" />

            <h1 className="sr-only">Account Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Account overview"
                        description="Your account information and status"
                    />

                    <div className="rounded-lg border divide-y divide-border">
                        <div className="px-4 py-3 flex items-center justify-between">
                            <span className="text-sm font-medium text-muted-foreground">Name</span>
                            <span className="text-sm text-foreground">{user.name}</span>
                        </div>

                        <div className="px-4 py-3 flex items-center justify-between">
                            <span className="text-sm font-medium text-muted-foreground">Email</span>
                            <span className="text-sm text-foreground">{user.email}</span>
                        </div>

                        <div className="px-4 py-3 flex items-center justify-between">
                            <span className="text-sm font-medium text-muted-foreground">Email verified</span>
                            <span className="text-sm">
                                {user.email_verified_at ? (
                                    <Badge variant="secondary">Verified</Badge>
                                ) : (
                                    <Badge variant="destructive">Unverified</Badge>
                                )}
                            </span>
                        </div>

                        <div className="px-4 py-3 flex items-center justify-between">
                            <span className="text-sm font-medium text-muted-foreground">Account created</span>
                            <span className="text-sm text-foreground">
                                {new Date(user.created_at).toLocaleDateString(undefined, {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                })}
                            </span>
                        </div>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
