import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { BulkActionToolbar } from '@/components/issues/bulk-action-toolbar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Issue {
    id: number;
    title: string;
    type: string;
    status: string;
    priority: string;
    occurrence_count: number;
    last_seen_at: string;
    first_seen_at: string;
    assignee: { id: number; name: string; email: string } | null;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    status: string;
    type: string | null;
    assignee_id: string | null;
    search: string | null;
    priority: string | null;
    sort: string;
}

interface Organization {
    id: number;
    slug: string;
    name: string;
}

interface Project {
    id: number;
    slug: string;
    name: string;
}

interface Environment {
    id: number;
    slug: string;
    name: string;
}

interface Props {
    organization: Organization;
    project: Project;
    environment: Environment;
    issues: Issue[];
    pagination: Pagination;
    filters: Filters;
}

const statusVariantMap: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    open: 'destructive',
    resolved: 'secondary',
    ignored: 'outline',
};

const priorityVariantMap: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    critical: 'destructive',
    high: 'default',
    medium: 'secondary',
    low: 'outline',
};

export default function IssuesIndex({
    organization,
    project,
    environment,
    issues,
    pagination,
    filters,
}: Props) {
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [search, setSearch] = useState(filters.search ?? '');

    const baseUrl = `/organizations/${organization.slug}/projects/${project.slug}/environments/${environment.slug}/issues`;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: organization.name,
            href: `/organizations/${organization.slug}`,
        },
        {
            title: project.name,
            href: `/organizations/${organization.slug}/projects/${project.slug}`,
        },
        { title: environment.name, href: baseUrl },
        { title: 'Issues', href: baseUrl },
    ];

    function filterByStatus(status: string) {
        router.get(baseUrl, { ...filters, status }, { preserveState: true });
    }

    function filterByPriority(priority: string) {
        router.get(
            baseUrl,
            { ...filters, priority: priority || undefined },
            { preserveState: true },
        );
    }

    function submitSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get(
            baseUrl,
            { ...filters, search: search || undefined },
            { preserveState: true },
        );
    }

    function toggleSelect(id: number) {
        setSelectedIds((prev) =>
            prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id],
        );
    }

    function toggleAll() {
        if (selectedIds.length === issues.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(issues.map((i) => i.id));
        }
    }

    const statuses = ['open', 'resolved', 'ignored'];
    const priorities = ['', 'critical', 'high', 'medium', 'low'];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Issues" />
            <div className="flex flex-col gap-4 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Issues</h1>
                </div>

                {/* Status tabs */}
                <div className="flex gap-1 border-b">
                    {statuses.map((s) => (
                        <button
                            key={s}
                            onClick={() => filterByStatus(s)}
                            className={`px-4 py-2 text-sm font-medium capitalize transition-colors ${
                                filters.status === s
                                    ? 'border-b-2 border-primary text-foreground'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {s}
                        </button>
                    ))}
                </div>

                {/* Filters */}
                <div className="flex flex-wrap gap-3">
                    <form onSubmit={submitSearch} className="flex gap-2">
                        <Input
                            placeholder="Search issues..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-64"
                        />
                        <Button type="submit" size="sm" variant="outline">
                            Search
                        </Button>
                    </form>
                    <select
                        className="rounded-md border bg-background px-3 py-1.5 text-sm"
                        value={filters.priority ?? ''}
                        onChange={(e) => filterByPriority(e.target.value)}
                    >
                        {priorities.map((p) => (
                            <option key={p} value={p}>
                                {p
                                    ? p.charAt(0).toUpperCase() + p.slice(1)
                                    : 'All priorities'}
                            </option>
                        ))}
                    </select>
                </div>

                {/* Bulk action toolbar */}
                <BulkActionToolbar
                    selectedIds={selectedIds}
                    bulkUrl={`${baseUrl}/bulk`}
                    onClear={() => setSelectedIds([])}
                />

                {/* Issues table */}
                <div className="rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="w-8 px-4 py-3">
                                    <Checkbox
                                        checked={
                                            selectedIds.length ===
                                                issues.length &&
                                            issues.length > 0
                                        }
                                        onCheckedChange={toggleAll}
                                    />
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Issue
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Type
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Priority
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Count
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Last Seen
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {issues.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No issues found.
                                    </td>
                                </tr>
                            )}
                            {issues.map((issue) => (
                                <tr
                                    key={issue.id}
                                    className="hover:bg-muted/20"
                                >
                                    <td className="px-4 py-3">
                                        <Checkbox
                                            checked={selectedIds.includes(
                                                issue.id,
                                            )}
                                            onCheckedChange={() =>
                                                toggleSelect(issue.id)
                                            }
                                        />
                                    </td>
                                    <td className="px-4 py-3">
                                        <a
                                            href={`${baseUrl}/${issue.id}`}
                                            className="line-clamp-1 font-medium hover:underline"
                                        >
                                            {issue.title}
                                        </a>
                                        {issue.assignee && (
                                            <p className="text-xs text-muted-foreground">
                                                Assigned to{' '}
                                                {issue.assignee.name}
                                            </p>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant="outline">
                                            {issue.type}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                priorityVariantMap[
                                                    issue.priority
                                                ] ?? 'outline'
                                            }
                                        >
                                            {issue.priority}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                statusVariantMap[
                                                    issue.status
                                                ] ?? 'outline'
                                            }
                                        >
                                            {issue.status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums">
                                        {issue.occurrence_count.toLocaleString()}
                                    </td>
                                    <td className="px-4 py-3 text-right text-muted-foreground">
                                        {new Date(
                                            issue.last_seen_at,
                                        ).toLocaleDateString()}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {pagination.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <p>
                            Showing page {pagination.current_page} of{' '}
                            {pagination.last_page} ({pagination.total} total)
                        </p>
                        <div className="flex gap-2">
                            {pagination.current_page > 1 && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() =>
                                        router.get(baseUrl, {
                                            ...filters,
                                            page: pagination.current_page - 1,
                                        })
                                    }
                                >
                                    Previous
                                </Button>
                            )}
                            {pagination.current_page < pagination.last_page && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() =>
                                        router.get(baseUrl, {
                                            ...filters,
                                            page: pagination.current_page + 1,
                                        })
                                    }
                                >
                                    Next
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
