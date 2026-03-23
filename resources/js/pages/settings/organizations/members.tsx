import { Head, router, useForm } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

interface PendingInvitation {
    id: number;
    name: string | null;
    email: string;
    role: Role | null;
    expires_at: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organization members', href: '#' },
];

function InviteDialog({
    organization,
    roles,
}: {
    organization: Organization;
    roles: Role[];
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '', email: '', organization_role_id: '' });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(`/settings/organizations/${organization.slug}/members/invitations`, {
            onSuccess: () => {
                toast.success('Invitation sent');
                setOpen(false);
                form.reset();
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <UserPlus className="mr-2 size-4" />
                    Invite member
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Invite a member</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 pt-2">
                    <div className="grid gap-2">
                        <Label htmlFor="invite-name">Name</Label>
                        <Input
                            id="invite-name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="Full name"
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="invite-email">Email address</Label>
                        <Input
                            id="invite-email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            placeholder="email@example.com"
                            required
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="invite-role">Role</Label>
                        <Select
                            value={form.data.organization_role_id}
                            onValueChange={(v) => form.setData('organization_role_id', v)}
                        >
                            <SelectTrigger id="invite-role">
                                <SelectValue placeholder="Select a role" />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem key={role.id} value={String(role.id)}>
                                        {role.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.organization_role_id} />
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Send invitation
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function OrganizationMembers({
    organization,
    members,
    pendingInvitations,
    roles,
    currentMemberId,
}: {
    organization: Organization;
    members: Member[];
    pendingInvitations: PendingInvitation[];
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

    function revokeInvitation(invitationId: number) {
        router.delete(`/organizations/${organization.slug}/invitations/${invitationId}`, {
            onSuccess: () => toast.success('Invitation revoked'),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organization members" />

            <h1 className="sr-only">Organization Members</h1>

            <SettingsLayout>
                <div className="space-y-8">
                    {/* Members */}
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <Heading
                                variant="small"
                                title="Members"
                                description="Manage who has access to this organization"
                            />
                            <InviteDialog organization={organization} roles={roles} />
                        </div>

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
                                                        className="text-destructive/70 hover:text-destructive hover:bg-destructive/10"
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

                    {/* Pending invitations */}
                    {pendingInvitations.length > 0 && (
                        <div className="space-y-4">
                            <Heading
                                variant="small"
                                title="Pending invitations"
                                description="Invitations that have not been accepted yet"
                            />

                            <div className="divide-y divide-border rounded-lg border">
                                {pendingInvitations.map((invitation) => (
                                    <div
                                        key={invitation.id}
                                        className="flex items-center justify-between gap-4 px-4 py-3"
                                    >
                                        <div className="min-w-0 space-y-0.5">
                                            {invitation.name && (
                                                <p className="truncate text-sm font-medium">
                                                    {invitation.name}
                                                </p>
                                            )}
                                            <p className="truncate text-xs text-muted-foreground">
                                                {invitation.email}
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 items-center gap-2">
                                            {invitation.role && (
                                                <Badge variant="outline">{invitation.role.name}</Badge>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive/70 hover:text-destructive hover:bg-destructive/10"
                                                onClick={() => revokeInvitation(invitation.id)}
                                            >
                                                Revoke
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
