import { router, usePage } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import { useState } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

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

function parseCustomDates(period: string): { from: string; to: string } {
    if (!period.includes('..')) return { from: '', to: '' };
    const [from, to] = period.split('..');
    return { from: from.slice(0, 10), to: to.slice(0, 10) };
}

interface PeriodSelectorProps {
    current: string;
}

export function PeriodSelector({ current }: PeriodSelectorProps) {
    const { url } = usePage();
    const isCustom = current.includes('..');
    const parsed = parseCustomDates(current);

    const [from, setFrom] = useState(parsed.from);
    const [to, setTo] = useState(parsed.to);
    const [open, setOpen] = useState(false);

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

    function handleApply() {
        if (!from || !to) return;
        setOpen(false);
        handleChange(`${from}..${to}T23:59:59`);
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

            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <button
                        className={`rounded px-2 py-1 transition-colors ${
                            isCustom
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        <CalendarDays className="size-4" />
                    </button>
                </PopoverTrigger>
                <PopoverContent align="end" className="w-64 p-3">
                    <div className="flex flex-col gap-3">
                        <div className="flex flex-col gap-1.5">
                            <label className="text-xs font-medium text-muted-foreground">From</label>
                            <input
                                type="date"
                                value={from}
                                max={to || undefined}
                                onChange={(e) => setFrom(e.target.value)}
                                className="rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-xs font-medium text-muted-foreground">To</label>
                            <input
                                type="date"
                                value={to}
                                min={from || undefined}
                                onChange={(e) => setTo(e.target.value)}
                                className="rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            />
                        </div>
                        <button
                            onClick={handleApply}
                            disabled={!from || !to}
                            className="rounded-md bg-foreground px-3 py-1.5 text-sm font-medium text-background transition-opacity disabled:opacity-40"
                        >
                            Apply
                        </button>
                    </div>
                </PopoverContent>
            </Popover>
        </div>
    );
}
