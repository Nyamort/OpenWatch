import { router, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';

const FILTERS = [
    { value: 'open', label: 'Open' },
    { value: 'unassigned', label: 'Unassigned' },
    { value: 'mine', label: 'Mine' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'ignored', label: 'Ignored' },
] as const;

type FilterValue = (typeof FILTERS)[number]['value'];

interface StatusFilterProps {
    current: FilterValue | string;
    counts: Record<string, number>;
}

export function StatusFilter({ current, counts }: StatusFilterProps) {
    const { url } = usePage();

    function handleChange(filter: string) {
        const urlObj = new URL(url, window.location.origin);
        urlObj.searchParams.set('filter', filter);
        urlObj.searchParams.delete('page');
        urlObj.searchParams.delete('status');
        urlObj.searchParams.delete('assignee_id');
        router.get(urlObj.pathname + urlObj.search, {}, {
            preserveScroll: true,
            preserveState: true,
            only: ['issues', 'pagination', 'filter', 'filterCounts'],
        });
    }

    return (
        <div className="flex gap-1 rounded-lg border bg-muted p-1">
            {FILTERS.map((f) => (
                <button
                    key={f.value}
                    onClick={() => handleChange(f.value)}
                    className={`flex cursor-pointer items-center gap-1.5 rounded px-3 py-1 text-sm font-medium transition-colors ${
                        current === f.value
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    }`}
                >
                    {f.label}
                    {counts[f.value] !== undefined && (
                        <Badge variant="secondary">
                            {counts[f.value].toLocaleString()}
                        </Badge>
                    )}
                </button>
            ))}
        </div>
    );
}
