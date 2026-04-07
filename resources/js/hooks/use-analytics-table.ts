import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useDebounceCallback } from '@/hooks/use-debounce-callback';

interface UseAnalyticsTableOptions {
    search: string;
    only: string[];
}

export function useAnalyticsTable<TSortKey extends string>({
    search,
    only,
}: UseAnalyticsTableOptions) {
    const { url } = usePage();
    const [searchValue, setSearchValue] = useState(search);

    function navigate(overrides: Record<string, string | undefined>) {
        const base = new URL(url, window.location.origin);
        const params = Object.fromEntries(
            Object.entries({
                ...Object.fromEntries(base.searchParams),
                ...overrides,
            }).filter(([, v]) => v !== undefined),
        );
        router.get(base.pathname, params, {
            preserveScroll: true,
            preserveState: true,
            only,
        });
    }

    const debouncedNavigate = useDebounceCallback(
        (value: string) => navigate({ search: value, page: undefined }),
        300,
    );

    function handleSearchChange(value: string) {
        setSearchValue(value);
        debouncedNavigate(value);
    }

    function handlePage(page: number) {
        navigate({ page: String(page) });
    }

    function handleSort(
        key: TSortKey,
        currentSort: TSortKey,
        currentDirection: 'asc' | 'desc',
    ) {
        const dir =
            currentSort === key && currentDirection === 'desc' ? 'asc' : 'desc';
        navigate({ sort: key, direction: dir, page: undefined });
    }

    return {
        searchValue,
        handleSearch: handleSearchChange,
        handlePage,
        handleSort,
    };
}
