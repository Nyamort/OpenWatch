import { Head, router } from '@inertiajs/react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface Role {
    id: number;
    name: string;
    slug: string;
}

interface Member {
    id: number;
    user_id: number;
    user: { id: number; name: string; email: string };
    role: Role | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organization members', href: '#' },
];

export default function OrganizationMembers({
    organization,
    members,
    roles,
    currentMemberId,
}: {
    organization: Organization;
    members: Member[];
    roles: Role[];
    currentMemberId: number;
}) {
    const base = `/settings/organizations/${organization.slug}`;

    function updateRole(memberId: number, roleId: string) {
        router.patch(
            `${base}/members/${memberId}`,
            { role_id: Number(roleId) },
            { onSuccess: () => toast.success('Role updated') },
        );
    }

    function removeMember(memberId: number) {
        router.delete(`${base}/members/${memberId}`, {
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
                            members.map((member) => {
                                const isSelf = member.id === currentMemberId;

                                return (
                                    <div
                                        key={member.id}
                                        className="flex items-center justify-between gap-4 px-4 py-3"
                                    >
                                        <div className="min-w-0 space-y-0.5">
                                            <div className="flex items-center gap-2">
                                                <p className="truncate text-sm font-medium">
                                                    {member.user.name}
                                                </p>
                                                {isSelf && (
                                                    <Badge variant="secondary" className="text-xs">
                                                        You
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {member.user.email}
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 items-center gap-2">
                                            {isSelf ? (
                                                <Badge variant="outline">{member.role?.name}</Badge>
                                            ) : (
                                                <Select
                                                    value={String(member.role?.id ?? '')}
                                                    onValueChange={(value) =>
                                                        updateRole(member.id, value)
                                                    }
                                                >
                                                    <SelectTrigger className="h-8 w-32 text-xs">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {roles.map((role) => (
                                                            <SelectItem
                                                                key={role.id}
                                                                value={String(role.id)}
                                                            >
                                                                {role.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            )}

                                            {!isSelf && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-muted-foreground hover:text-destructive"
                                                    onClick={() => removeMember(member.id)}
                                                >
                                                    Remove
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
