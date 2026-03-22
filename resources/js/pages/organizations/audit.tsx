import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { useState } from 'react';

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
    metadata: Record<string, unknown> | null;
    ip: string | null;
    user_agent: string | null;
    created_at: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Filters {
    event_type?: string;
    actor_id?: string;
    date_from?: string;
    date_to?: string;
}

interface Props {
    organization: Organization;
    events: { data: AuditEvent[] } & Pagination;
    filters: Filters;
}

const EVENT_TYPE_COLORS: Record<string, 'default' | 'destructive' | 'secondary' | 'outline'> = {
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

export default function AuditLog({ organization, events, filters }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Organizations', href: '/organizations' },
        { title: organization.name, href: `/organizations/${organization.slug}` },
        { title: 'Audit Log', href: `/organizations/${organization.slug}/audit` },
    ];

    const [form, setForm] = useState<Filters>({
        event_type: filters.event_type ?? '',
        actor_id: filters.actor_id ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    const applyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        const params: Record<string, string> = {};
        if (form.event_type) params.event_type = form.event_type;
        if (form.actor_id) params.actor_id = form.actor_id;
        if (form.date_from) params.date_from = form.date_from;
        if (form.date_to) params.date_to = form.date_to;
        router.get(`/organizations/${organization.slug}/audit`, params, { preserveState: true });
    };

    const clearFilters = () => {
        setForm({ event_type: '', actor_id: '', date_from: '', date_to: '' });
        router.get(`/organizations/${organization.slug}/audit`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Audit Log — ${organization.name}`} />

            <div className="max-w-6xl mx-auto px-4 py-8 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Audit Log</h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Immutable record of all critical actions in {organization.name}.
                        </p>
                    </div>
                    <Link href={`/organizations/${organization.slug}`}>
                        <Button variant="outline" size="sm">← Back</Button>
                    </Link>
                </div>

                {/* Filters */}
                <form onSubmit={applyFilters} className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="space-y-1">
                            <Label htmlFor="event_type">Event type</Label>
                            <Input
                                id="event_type"
                                placeholder="e.g. member_invited"
                                value={form.event_type}
                                onChange={(e) => setForm({ ...form, event_type: e.target.value })}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="actor_id">Actor ID</Label>
                            <Input
                                id="actor_id"
                                type="number"
                                placeholder="User ID"
                                value={form.actor_id}
                                onChange={(e) => setForm({ ...form, actor_id: e.target.value })}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="date_from">From</Label>
                            <Input
                                id="date_from"
                                type="date"
                                value={form.date_from}
                                onChange={(e) => setForm({ ...form, date_from: e.target.value })}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="date_to">To</Label>
                            <Input
                                id="date_to"
                                type="date"
                                value={form.date_to}
                                onChange={(e) => setForm({ ...form, date_to: e.target.value })}
                            />
                        </div>
                    </div>
                    <div className="mt-4 flex gap-2">
                        <Button type="submit" size="sm">Apply filters</Button>
                        <Button type="button" variant="ghost" size="sm" onClick={clearFilters}>Clear</Button>
                    </div>
                </form>

                {/* Event list */}
                <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    {events.data.length === 0 ? (
                        <div className="text-center py-16 text-gray-500 dark:text-gray-400">
                            No audit events found.
                        </div>
                    ) : (
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actor</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {events.data.map((event) => (
                                    <tr key={event.id} className="hover:bg-gray-50 dark:hover:bg-gray-750">
                                        <td className="px-4 py-3">
                                            <Badge variant={EVENT_TYPE_COLORS[event.event_type] ?? 'outline'}>
                                                {event.event_type.replace(/_/g, ' ')}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {event.actor_id ?? <span className="text-gray-400 italic">anonymized</span>}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {event.target_type && event.target_id
                                                ? `${event.target_type} #${event.target_id}`
                                                : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-sm font-mono text-gray-500">
                                            {event.ip ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-500">
                                            {new Date(event.created_at).toLocaleString()}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* Pagination */}
                {events.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-gray-500">
                        <span>
                            Page {events.current_page} of {events.last_page} — {events.total} events total
                        </span>
                        <div className="flex gap-1">
                            {events.links.map((link, i) => (
                                <button
                                    key={i}
                                    disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url)}
                                    className={[
                                        'px-3 py-1 rounded border text-xs',
                                        link.active
                                            ? 'bg-blue-600 text-white border-blue-600'
                                            : 'border-gray-300 hover:bg-gray-50 disabled:opacity-40',
                                    ].join(' ')}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
