import { usePage } from '@inertiajs/react';
import { getStoredPeriod } from '@/components/analytics/period-selector';

const DEFAULT_VALUES: Record<string, string> = {
    period: '24h',
};

const STORED_VALUES: Record<string, () => string | null> = {
    period: getStoredPeriod,
};

const PERSISTENT_FILTERS = ['period'] as const;

export function useAnalyticsHref(): (base: string) => string {
    const currentUrl = new URL(usePage().url, window.location.origin);

    return function (base: string): string {
        const u = new URL(base, window.location.origin);

        for (const key of PERSISTENT_FILTERS) {
            const value =
                currentUrl.searchParams.get(key) ??
                STORED_VALUES[key]?.() ??
                null;

            if (value !== null && value !== DEFAULT_VALUES[key]) {
                u.searchParams.set(key, value);
            }
        }

        return u.pathname + u.search;
    };
}
