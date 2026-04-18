import { ChevronDown, Info } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { CodeBlock } from '@/components/ui/code-block';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const RATE_OPTIONS = [
    { label: '0%', value: '0' },
    { label: '1%', value: '0.01' },
    { label: '5%', value: '0.05' },
    { label: '10%', value: '0.1' },
    { label: '25%', value: '0.25' },
    { label: '50%', value: '0.5' },
    { label: '75%', value: '0.75' },
    { label: '100%', value: '1.0' },
];

const SAMPLING_EVENTS = [
    {
        key: 'exceptions',
        label: 'Exceptions',
        envVar: 'NIGHTWATCH_EXCEPTION_SAMPLE_RATE',
    },
    {
        key: 'requests',
        label: 'Requests',
        envVar: 'NIGHTWATCH_REQUEST_SAMPLE_RATE',
    },
    {
        key: 'commands',
        label: 'Commands',
        envVar: 'NIGHTWATCH_COMMAND_SAMPLE_RATE',
    },
];

const IGNORE_EVENTS = [
    {
        key: 'cache',
        label: 'Cache Events',
        envVar: 'NIGHTWATCH_IGNORE_CACHE_EVENTS',
    },
    { key: 'mail', label: 'Mail Events', envVar: 'NIGHTWATCH_IGNORE_MAIL' },
    {
        key: 'notifications',
        label: 'Notifications',
        envVar: 'NIGHTWATCH_IGNORE_NOTIFICATIONS',
    },
    {
        key: 'outgoing',
        label: 'Outgoing requests',
        envVar: 'NIGHTWATCH_IGNORE_OUTGOING_REQUESTS',
    },
    {
        key: 'queries',
        label: 'Database Queries',
        envVar: 'NIGHTWATCH_IGNORE_QUERIES',
    },
];

const DEFAULT_SAMPLING_RATES = {
    exceptions: '1.0',
    requests: '0.1',
    commands: '1.0',
};
const DEFAULT_INCLUDED_EVENTS = Object.fromEntries(
    IGNORE_EVENTS.map((e) => [e.key, true]),
);

function useClickOutside(
    ref: React.RefObject<HTMLElement | null>,
    onClose: () => void,
    open: boolean,
    ignorePortal = false,
) {
    useEffect(() => {
        if (!open) return;
        function handler(e: MouseEvent) {
            const target = e.target as Element;
            if (ref.current && !ref.current.contains(target)) {
                if (
                    !ignorePortal ||
                    !target.closest?.('[data-radix-popper-content-wrapper]')
                ) {
                    onClose();
                }
            }
        }
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [open, ref, onClose, ignorePortal]);
}

function SamplingRatesDropdown({
    rates,
    onChange,
}: {
    rates: Record<string, string>;
    onChange: (key: string, value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);
    useClickOutside(ref, () => setOpen(false), open, true);

    return (
        <div ref={ref} className="relative">
            <button
                onClick={() => setOpen((v) => !v)}
                className="flex items-center gap-1.5 rounded-md border border-zinc-700 bg-zinc-800 px-2.5 py-1.5 text-xs text-zinc-100 transition-colors hover:bg-zinc-700 focus:outline-none"
            >
                Global sampling rates
                <ChevronDown className="size-3 shrink-0 text-zinc-400" />
            </button>
            {open && (
                <div className="absolute top-full left-0 z-50 mt-1 min-w-[220px] rounded-md border border-zinc-700 bg-zinc-800 p-3 shadow-lg">
                    <div className="space-y-2">
                        {SAMPLING_EVENTS.map(({ key, label }) => (
                            <div
                                key={key}
                                className="flex items-center justify-between gap-6"
                            >
                                <span className="text-xs text-zinc-300">
                                    {label}
                                </span>
                                <Select
                                    value={rates[key]}
                                    onValueChange={(v) => onChange(key, v)}
                                >
                                    <SelectTrigger className="h-7 w-24 border-zinc-600 bg-zinc-900 text-xs text-zinc-100 focus:ring-zinc-500">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {RATE_OPTIONS.map((opt) => (
                                            <SelectItem
                                                key={opt.value}
                                                value={opt.value}
                                                className="text-xs"
                                            >
                                                {opt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function EventFilterDropdown({
    included,
    onChange,
}: {
    included: Record<string, boolean>;
    onChange: (key: string, value: boolean) => void;
}) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);
    useClickOutside(ref, () => setOpen(false), open);

    const excludedCount = IGNORE_EVENTS.filter((e) => !included[e.key]).length;
    const triggerLabel =
        excludedCount === 0
            ? 'All events included'
            : excludedCount === IGNORE_EVENTS.length
              ? 'All events excluded'
              : `${excludedCount} of ${IGNORE_EVENTS.length} events excluded`;

    return (
        <div ref={ref} className="relative">
            <button
                onClick={() => setOpen((v) => !v)}
                className="flex items-center gap-1.5 rounded-md border border-zinc-700 bg-zinc-800 px-2.5 py-1.5 text-xs text-zinc-100 transition-colors hover:bg-zinc-700 focus:outline-none"
            >
                {triggerLabel}
                <ChevronDown className="size-3 shrink-0 text-zinc-400" />
            </button>
            {open && (
                <div className="absolute top-full left-0 z-50 mt-1 min-w-[200px] rounded-md border border-zinc-700 bg-zinc-800 p-3 shadow-lg">
                    <div className="space-y-2">
                        {IGNORE_EVENTS.map(({ key, label }) => (
                            <label
                                key={key}
                                className="flex cursor-pointer items-center gap-2"
                            >
                                <Checkbox
                                    checked={included[key]}
                                    onCheckedChange={(checked) =>
                                        onChange(key, !!checked)
                                    }
                                    className="size-3.5 border-zinc-500"
                                />
                                <span className="text-xs text-zinc-300">
                                    {label}
                                </span>
                            </label>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

export function WizardStep3({
    onNext,
    onBack,
}: {
    onNext: () => void;
    onBack: () => void;
}) {
    const [samplingRates, setSamplingRates] = useState<Record<string, string>>(
        DEFAULT_SAMPLING_RATES,
    );
    const [includedEvents, setIncludedEvents] = useState<
        Record<string, boolean>
    >(DEFAULT_INCLUDED_EVENTS);

    const samplingEnvLines = SAMPLING_EVENTS.filter(
        (e) => samplingRates[e.key] !== '1.0',
    ).map((e) => ({
        envVar: e.envVar,
        value: samplingRates[e.key],
    }));
    const ignoreEnvLines = IGNORE_EVENTS.filter(
        (e) => !includedEvents[e.key],
    ).map((e) => ({
        envVar: e.envVar,
        value: 'true',
    }));
    const allEnvLines = [...samplingEnvLines, ...ignoreEnvLines];

    return (
        <div className="mt-4 space-y-4">
            <p className="text-sm text-zinc-400">
                Sampling reduces the number of events Nightwatch records,
                helping control storage and performance costs. We recommend
                starting at{' '}
                <span className="font-medium text-zinc-200">10%</span> for
                requests and adjusting based on your traffic.
            </p>

            <div className="flex flex-wrap items-center gap-2">
                <SamplingRatesDropdown
                    rates={samplingRates}
                    onChange={(key, value) =>
                        setSamplingRates((prev) => ({ ...prev, [key]: value }))
                    }
                />
                <EventFilterDropdown
                    included={includedEvents}
                    onChange={(key, value) =>
                        setIncludedEvents((prev) => ({ ...prev, [key]: value }))
                    }
                />
            </div>

            {allEnvLines.length > 0 && (
                <CodeBlock
                    code={allEnvLines
                        .map((l) => `${l.envVar}=${l.value}`)
                        .join('\n')}
                    language="bash"
                    copyable
                    className="rounded-md bg-zinc-900 px-4 py-3"
                />
            )}

            <div className="flex items-center gap-2 rounded-md border border-blue-800 bg-blue-950/40 px-3 py-2">
                <Info className="size-4 shrink-0 text-blue-400" />
                <span className="text-xs text-blue-300">
                    Learn more about additional sampling options
                </span>
            </div>

            <div className="flex justify-between">
                <Button
                    variant="ghost"
                    onClick={onBack}
                    className="text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100"
                >
                    Back
                </Button>
                <Button
                    onClick={onNext}
                    className="bg-blue-600 text-white hover:bg-blue-700"
                >
                    Next
                </Button>
            </div>
        </div>
    );
}
