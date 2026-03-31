import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface AuditEvent {
    id: number;
    event_type: string;
    actor_id: number | null;
    target_type: string | null;
    target_id: number | null;
    ip: string | null;
    created_at: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Filters {
    event_type?: string;
    actor_id?: string;
    date_from?: string;
    date_to?: string;
}

const EVENT_TYPE_COLORS: Record<
    string,
    'default' | 'destructive' | 'secondary' | 'outline'
> = {
    organization_created: 'default',
    organization_updated: 'secondary',
    organization_deleted: 'destructive',
    member_invited: 'default',
    member_removed: 'destructive',
    ownership_transferred: 'secondary',
    role_changed: 'secondary',
    token_rotated: 'secondary',
    token_revoked: 'destructive',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organization audit', href: '#' },
];

export default function OrganizationAudit({
    organization,
    events,
    filters,
}: {
    organization: Organization;
    events: { data: AuditEvent[] } & Pagination;
    filters: Filters;
}) {
    const [form, setForm] = useState<Filters>({
        event_type: filters.event_type ?? '',
        actor_id: filters.actor_id ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    const baseUrl = `/settings/organizations/${organization.slug}/audit`;

    function applyFilters(e: React.FormEvent) {
        e.preventDefault();
        const params: Record<string, string> = {};
        if (form.event_type) params.event_type = form.event_type;
        if (form.actor_id) params.actor_id = form.actor_id;
        if (form.date_from) params.date_from = form.date_from;
        if (form.date_to) params.date_to = form.date_to;
        router.get(baseUrl, params, { preserveState: true });
    }

    function clearFilters() {
        setForm({ event_type: '', actor_id: '', date_from: '', date_to: '' });
        router.get(baseUrl);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organization audit" />

            <h1 className="sr-only">Organization Audit</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Audit log"
                        description="Immutable record of all critical actions"
                    />

                    <form
                        onSubmit={applyFilters}
                        className="grid grid-cols-2 gap-3"
                    >
                        <div className="space-y-1">
                            <Label htmlFor="event_type">Event type</Label>
                            <Input
                                id="event_type"
                                placeholder="e.g. member_invited"
                                value={form.event_type}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        event_type: e.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="actor_id">Actor ID</Label>
                            <Input
                                id="actor_id"
                                type="number"
                                placeholder="User ID"
                                value={form.actor_id}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        actor_id: e.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="date_from">From</Label>
                            <Input
                                id="date_from"
                                type="date"
                                value={form.date_from}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        date_from: e.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="date_to">To</Label>
                            <Input
                                id="date_to"
                                type="date"
                                value={form.date_to}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        date_to: e.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="col-span-2 flex gap-2">
                            <Button type="submit" size="sm">
                                Apply
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={clearFilters}
                            >
                                Clear
                            </Button>
                        </div>
                    </form>

                    <div className="divide-y divide-border overflow-hidden rounded-lg border">
                        {events.data.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No audit events found.
                            </p>
                        ) : (
                            events.data.map((event) => (
                                <div
                                    key={event.id}
                                    className="flex items-center justify-between px-4 py-3"
                                >
                                    <div className="space-y-0.5">
                                        <Badge
                                            variant={
                                                EVENT_TYPE_COLORS[
                                                    event.event_type
                                                ] ?? 'outline'
                                            }
                                        >
                                            {event.event_type.replace(
                                                /_/g,
                                                ' ',
                                            )}
                                        </Badge>
                                        {event.target_type &&
                                            event.target_id && (
                                                <p className="text-xs text-muted-foreground">
                                                    {event.target_type} #
                                                    {event.target_id}
                                                </p>
                                            )}
                                    </div>
                                    <div className="space-y-0.5 text-right">
                                        <p className="text-xs text-muted-foreground">
                                            {event.actor_id
                                                ? `User #${event.actor_id}`
                                                : 'anonymized'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {new Date(
                                                event.created_at,
                                            ).toLocaleString()}
                                        </p>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {events.last_page > 1 && (
                        <div className="flex items-center justify-between text-sm text-muted-foreground">
                            <span>
                                Page {events.current_page} of {events.last_page}{' '}
                                — {events.total} events
                            </span>
                            <div className="flex gap-1">
                                {events.links.map((link, i) => (
                                    <button
                                        key={i}
                                        disabled={!link.url}
                                        onClick={() =>
                                            link.url && router.get(link.url)
                                        }
                                        className={[
                                            'rounded border px-3 py-1 text-xs',
                                            link.active
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border hover:bg-muted/40 disabled:opacity-40',
                                        ].join(' ')}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
