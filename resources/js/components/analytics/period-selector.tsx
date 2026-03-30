import { router, usePage } from '@inertiajs/react';

const PERIODS = [
    { value: '1h', label: '1H' },
    { value: '24h', label: '24H' },
    { value: '7d', label: '7D' },
    { value: '14d', label: '14D' },
    { value: '30d', label: '30D' },
] as const;

const STORAGE_KEY = 'analytics_period';

export function getStoredPeriod(): string | null {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

interface PeriodSelectorProps {
    current: string;
}

export function PeriodSelector({ current }: PeriodSelectorProps) {
    const { url } = usePage();

    function handleChange(period: string) {
        try {
            localStorage.setItem(STORAGE_KEY, period);
        } catch {
            // localStorage unavailable, continue without persisting
        }
        const urlObj = new URL(url, window.location.origin);
        urlObj.searchParams.set('period', period);
        router.get(urlObj.pathname + urlObj.search, {}, { preserveScroll: true, preserveState: true });
    }

    return (
        <div className="flex gap-1 rounded-lg border bg-muted p-1">
            {PERIODS.map((p) => (
                <button
                    key={p.value}
                    onClick={() => handleChange(p.value)}
                    className={`rounded px-3 py-1 text-sm font-medium transition-colors ${
                        current === p.value
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    }`}
                >
                    {p.label}
                </button>
            ))}
        </div>
    );
}
