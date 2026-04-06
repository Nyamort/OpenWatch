import { ChevronDown, ChevronRight } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

export interface TimelineSpan {
    id: string;
    /** Short uppercase label, e.g. "COMMAND", "BOOTSTRAP" */
    label: string;
    /** Monospace detail shown next to the label, e.g. a class name or command */
    sublabel?: string;
    /** Duration in milliseconds. Null renders the span as a text marker instead of a bar. */
    durationMs: number | null;
    /** Start offset from the beginning of the trace, in milliseconds */
    offsetMs: number;
    color?: 'teal' | 'default';
    children?: TimelineSpan[];
}

interface TimelineProps {
    /** Total duration used as the 100 % reference for bar widths */
    totalDurationMs: number;
    spans: TimelineSpan[];
    className?: string;
}

interface FlatSpan {
    span: TimelineSpan;
    depth: number;
    hasChildren: boolean;
}

function flattenSpans(spans: TimelineSpan[], expandedIds: Set<string>, depth = 0): FlatSpan[] {
    const result: FlatSpan[] = [];
    for (const span of spans) {
        const hasChildren = !!span.children?.length;
        result.push({ span, depth, hasChildren });
        if (hasChildren && expandedIds.has(span.id)) {
            result.push(...flattenSpans(span.children!, expandedIds, depth + 1));
        }
    }
    return result;
}

function computeTicks(totalMs: number, targetCount = 4): number[] {
    const roughStep = totalMs / targetCount;
    const magnitude = Math.pow(10, Math.floor(Math.log10(roughStep)));
    const normalised = roughStep / magnitude;
    const niceStep = normalised <= 1 ? magnitude : normalised <= 2 ? 2 * magnitude : normalised <= 5 ? 5 * magnitude : 10 * magnitude;
    const ticks: number[] = [];
    for (let t = 0; t <= totalMs + niceStep; t += niceStep) {
        ticks.push(Math.round(t * 100) / 100);
    }
    return ticks;
}

function allExpandableIds(spans: TimelineSpan[]): string[] {
    const ids: string[] = [];
    for (const s of spans) {
        if (s.children?.length) {
            ids.push(s.id);
            ids.push(...allExpandableIds(s.children));
        }
    }
    return ids;
}


const LABEL_COL_PX = 256; // w-64

export function Timeline({ totalDurationMs, spans, className }: TimelineProps) {
    const [expandedIds, setExpandedIds] = useState<Set<string>>(
        () => new Set(allExpandableIds(spans)),
    );
    const [cursor, setCursor] = useState<{ x: number; ms: number } | null>(null);
    const innerRef = useRef<HTMLDivElement>(null);

    const ticks = computeTicks(totalDurationMs);
    const axisDurationMs = ticks[ticks.length - 1];

    const toggleExpand = useCallback((id: string) => {
        setExpandedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }, []);

    const handleMouseMove = useCallback(
        (e: React.MouseEvent<HTMLDivElement>) => {
            const rect = innerRef.current?.getBoundingClientRect();
            if (!rect) return;
            const x = e.clientX - rect.left;
            const barX = x - LABEL_COL_PX;
            const barWidth = rect.width - LABEL_COL_PX;
            if (barX < 0) return;
            const ms = (barX / barWidth) * axisDurationMs;
            setCursor({ x, ms });
        },
        [axisDurationMs],
    );

    const handleMouseLeave = useCallback(() => setCursor(null), []);

    const flatSpans = flattenSpans(spans, expandedIds);

    const pct = (ms: number) => `${(ms / axisDurationMs) * 100}%`;

    return (
        <div
            className={cn(
                'flex flex-col overflow-hidden rounded-lg border border-white/10 bg-surface font-mono text-xs',
                className,
            )}
        >
            {/* ── Single scrollable container ───────────────────────── */}
            <div className="overflow-auto min-h-200">
                <div
                    ref={innerRef}
                    className="relative"
                    style={{ minWidth: '800px' }}
                    onMouseMove={handleMouseMove}
                    onMouseLeave={handleMouseLeave}
                >
                    {/* ── Sticky header row ────────────────────────────── */}
                    <div className="sticky top-0 z-10 flex h-9 border-b border-white/10 bg-surface">
                        <div className="sticky left-0 z-20 flex w-64 shrink-0 items-center border-r border-white/10 bg-surface px-3">
                            <span className="font-sans text-xs font-semibold tracking-wide text-zinc-200">
                                Timeline
                            </span>
                        </div>
                        <div className="relative flex-1">
                            {ticks.map((ms, i) => (
                                <span
                                    key={ms}
                                    className="absolute top-1/2 -translate-y-1/2 text-[10px] text-zinc-600"
                                    style={{
                                        left: pct(ms),
                                        transform: i === 0 ? 'translateY(-50%)' : 'translate(-50%, -50%)',
                                    }}
                                >
                                    {ms}ms
                                </span>
                            ))}
                        </div>
                    </div>

                    {/* ── Span rows ────────────────────────────────────── */}
                    {flatSpans.map(({ span, depth, hasChildren }) => (
                        <div key={span.id} className="flex h-9">
                            {/* Label cell */}
                            <div
                                className="sticky left-0 z-10 flex w-64 shrink-0 items-center gap-1 border-r border-white/10 bg-surface"
                                style={{ paddingLeft: `${8 + depth * 16}px` }}
                            >
                                {hasChildren ? (
                                    <button
                                        onClick={() => toggleExpand(span.id)}
                                        className="flex size-4 shrink-0 items-center justify-center text-zinc-500 hover:text-zinc-300"
                                    >
                                        {expandedIds.has(span.id) ? (
                                            <ChevronDown className="size-3" />
                                        ) : (
                                            <ChevronRight className="size-3" />
                                        )}
                                    </button>
                                ) : (
                                    <span className="size-4 shrink-0" />
                                )}
                                <span className="shrink-0 text-[11px] uppercase tracking-wider text-white">
                                    {span.label}
                                </span>
                                {span.sublabel && (
                                    <span className="ml-1 truncate text-[10px] text-white/50">
                                        {span.sublabel}
                                    </span>
                                )}
                            </div>

                            {/* Bar cell */}
                            <div className="relative flex-1">
                                {span.durationMs === null ? (
                                    <span
                                        className="absolute top-1/2 -translate-y-1/2 pl-1 text-[10px] font-bold uppercase tracking-wider text-zinc-500"
                                        style={{ left: pct(span.offsetMs) }}
                                    >
                                        {span.label}
                                        {span.sublabel && (
                                            <span className="ml-1.5 font-normal normal-case text-zinc-600">
                                                {span.sublabel}
                                            </span>
                                        )}
                                    </span>
                                ) : (
                                    <div
                                        className={cn(
                                            'absolute top-1/2 flex h-8 -translate-y-1/2 items-center rounded-md border px-2 backdrop-blur-sm',
                                            span.color === 'teal'
                                                ? 'border-emerald-500 bg-emerald-500/20 text-white dark:border-emerald-700 dark:bg-emerald-700/20'
                                                : 'border-neutral-700 bg-neutral-800 text-white',
                                        )}
                                        style={{
                                            left: pct(span.offsetMs),
                                            width: pct(span.durationMs),
                                            minWidth: '2px',
                                        }}
                                    >
                                        <span className="shrink-0 text-[10px] uppercase tracking-wider">
                                            {span.label}
                                        </span>
                                        <span className="ml-1.5 shrink-0 text-[10px] opacity-70">
                                            {span.durationMs.toFixed(2)}ms
                                        </span>
                                        {span.sublabel && (
                                            <span className="ml-2 shrink-0 font-mono text-[10px] opacity-50">
                                                {span.sublabel}
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}

                    {/* Cursor line + tooltip */}
                    {cursor !== null && (
                        <>
                            <div
                                className="pointer-events-none absolute inset-y-0 w-px bg-amber-400/70"
                                style={{ left: `${cursor.x}px` }}
                            />
                            <div
                                className="pointer-events-none absolute top-1 z-30 -translate-x-1/2 rounded bg-amber-400 px-1.5 py-0.5 text-[10px] font-bold text-black"
                                style={{ left: `${cursor.x}px` }}
                            >
                                {`${Math.round(cursor.ms)}ms`}
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
