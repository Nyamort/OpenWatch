import { Head, router } from '@inertiajs/react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface Member {
    id: number;
    user: { id: number; name: string; email: string };
    role: { id: number; name: string; slug: string } | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organization members', href: '#' },
];

export default function OrganizationMembers({
    organization,
    members,
}: {
    organization: Organization;
    members: Member[];
}) {
    function removeMember(memberId: number) {
        router.delete(`/organizations/${organization.slug}/members/${memberId}`, {
            onSuccess: () => toast.success('Member removed'),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organization members" />

            <h1 className="sr-only">Organization Members</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Members"
                        description="Manage who has access to this organization"
                    />

                    <div className="divide-y divide-border rounded-lg border">
                        {members.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No members found.
                            </p>
                        ) : (
                            members.map((member) => (
                                <div
                                    key={member.id}
                                    className="flex items-center justify-between px-4 py-3"
                                >
                                    <div className="space-y-0.5">
                                        <p className="text-sm font-medium">{member.user.name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {member.user.email}
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-3">
                                        {member.role && (
                                            <Badge variant="secondary">{member.role.name}</Badge>
                                        )}
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => removeMember(member.id)}
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
