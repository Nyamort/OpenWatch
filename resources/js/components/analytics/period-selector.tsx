import { router, usePage } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { CalendarDays } from 'lucide-react';
import { useState } from 'react';
import { type DateRange } from 'react-day-picker';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';

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

function parseCustomPeriod(period: string): { range: DateRange; fromTime: string; toTime: string } | null {
    if (!period.includes('..')) return null;
    try {
        const [fromStr, toStr] = period.split('..');
        return {
            range: { from: parseISO(fromStr), to: parseISO(toStr) },
            fromTime: fromStr.length > 10 ? fromStr.slice(11, 16) : '00:00',
            toTime: toStr.length > 10 ? toStr.slice(11, 16) : '23:59',
        };
    } catch {
        return null;
    }
}

interface PeriodSelectorProps {
    current: string;
}

export function PeriodSelector({ current }: PeriodSelectorProps) {
    const { url } = usePage();
    const isCustom = current.includes('..');
    const parsed = parseCustomPeriod(current);

    const [open, setOpen] = useState(false);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(parsed?.range);
    const [fromTime, setFromTime] = useState(parsed?.fromTime ?? '00:00');
    const [toTime, setToTime] = useState(parsed?.toTime ?? '23:59');

    function handleChange(period: string) {
        try {
            localStorage.setItem(STORAGE_KEY, period);
        } catch {
            // localStorage unavailable
        }
        const urlObj = new URL(url, window.location.origin);
        urlObj.searchParams.set('period', period);
        router.get(urlObj.pathname + urlObj.search, {}, { preserveScroll: true, preserveState: true });
    }

    function handleApply() {
        if (!dateRange?.from || !dateRange?.to) return;
        const fromDate = format(dateRange.from, 'yyyy-MM-dd');
        const toDate = format(dateRange.to, 'yyyy-MM-dd');
        setOpen(false);
        handleChange(`${fromDate}T${fromTime}:00..${toDate}T${toTime}:00`);
    }

    const customLabel = parsed?.range.from && parsed?.range.to
        ? `${format(parsed.range.from, 'MMM d')} – ${format(parsed.range.to, 'MMM d')}`
        : null;

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
                        className={`flex items-center gap-1.5 rounded px-2 py-1 text-sm font-medium transition-colors ${
                            isCustom
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        <CalendarDays className="size-4 shrink-0" />
                        {isCustom && customLabel && (
                            <span className="text-xs">{customLabel}</span>
                        )}
                    </button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="end">
                    <Calendar
                        mode="range"
                        selected={dateRange}
                        onSelect={setDateRange}
                        numberOfMonths={2}
                        defaultMonth={dateRange?.from}
                        disabled={(date) => date > new Date()}
                    />
                    <Separator />
                    <div className="flex items-end gap-3 p-3">
                        <div className="flex flex-1 flex-col gap-1.5">
                            <label className="text-xs font-medium text-muted-foreground">
                                From
                                {dateRange?.from && (
                                    <span className="ml-1 font-normal">
                                        {format(dateRange.from, 'MMM d, yyyy')}
                                    </span>
                                )}
                            </label>
                            <Input
                                type="time"
                                value={fromTime}
                                onChange={(e) => setFromTime(e.target.value)}
                                className="h-8 text-sm"
                            />
                        </div>
                        <div className="flex flex-1 flex-col gap-1.5">
                            <label className="text-xs font-medium text-muted-foreground">
                                To
                                {dateRange?.to && (
                                    <span className="ml-1 font-normal">
                                        {format(dateRange.to, 'MMM d, yyyy')}
                                    </span>
                                )}
                            </label>
                            <Input
                                type="time"
                                value={toTime}
                                onChange={(e) => setToTime(e.target.value)}
                                className="h-8 text-sm"
                            />
                        </div>
                        <Button
                            size="sm"
                            onClick={handleApply}
                            disabled={!dateRange?.from || !dateRange?.to}
                        >
                            Apply
                        </Button>
                    </div>
                </PopoverContent>
            </Popover>
        </div>
    );
}
