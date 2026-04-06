import { ChevronDown, ChevronRight } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { ResizableHandle, ResizablePanel, ResizablePanelGroup } from '@/components/ui/resizable';
import { useTimelineZoom } from '@/hooks/use-timeline-zoom';
import { cn, formatDuration } from '@/lib/utils';

export interface TimelineSpan {
    id: string;
    /** Short uppercase label, e.g. "COMMAND", "BOOTSTRAP" */
    label: string;
    /** Monospace detail shown next to the label, e.g. a class name or command */
    sublabel?: string;
    /** Duration in microseconds. Null renders the span as a text marker instead of a bar. */
    durationUs: number | null;
    /** Start offset from the beginning of the trace, in microseconds */
    offsetUs: number;
    color?: 'teal' | 'default';
    children?: TimelineSpan[];
}

interface TimelineProps {
    /** Total duration used as the 100 % reference for bar widths */
    totalDurationUs: number;
    spans: TimelineSpan[];
    className?: string;
}

interface FlatSpan {
    span: TimelineSpan;
    depth: number;
    hasChildren: boolean;
}

const STICKY = 'sticky top-16 z-10 transition-[top] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:top-12';
const CURSOR_STICKY = 'pointer-events-none sticky top-25 z-30 h-0 transition-[top] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:top-21';
const ROW_HEIGHT = 'h-9';

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

function computeTicks(totalUs: number, targetCount = 4): number[] {
    const roughStep = totalUs / targetCount;
    const magnitude = Math.pow(10, Math.floor(Math.log10(roughStep)));
    const normalised = roughStep / magnitude;
    const niceStep = normalised <= 1 ? magnitude : normalised <= 2 ? 2 * magnitude : normalised <= 5 ? 5 * magnitude : 10 * magnitude;
    const ticks: number[] = [];
    for (let t = 0; t <= totalUs + niceStep; t += niceStep) {
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

function TimelineLabelRow({
    span,
    depth,
    hasChildren,
    isExpanded,
    onToggle,
}: {
    span: TimelineSpan;
    depth: number;
    hasChildren: boolean;
    isExpanded: boolean;
    onToggle: (id: string) => void;
}) {
    return (
        <div
            className={cn('flex shrink-0 items-center gap-1', ROW_HEIGHT)}
            style={{ paddingLeft: `${8 + depth * 16}px` }}
        >
            {hasChildren ? (
                <button
                    onClick={() => onToggle(span.id)}
                    className="flex size-4 shrink-0 items-center justify-center text-zinc-500 hover:text-zinc-300"
                >
                    {isExpanded ? <ChevronDown className="size-3" /> : <ChevronRight className="size-3" />}
                </button>
            ) : (
                <span className="size-4 shrink-0" />
            )}
            <span className="shrink-0 text-[11px] uppercase tracking-wider text-white">{span.label}</span>
            {span.sublabel && <span className="ml-1 truncate text-[10px] text-white/50">{span.sublabel}</span>}
        </div>
    );
}

function TimelineBarRow({ span, pct }: { span: TimelineSpan; pct: (us: number) => string }) {
    return (
        <div className={cn('relative shrink-0 overflow-hidden', ROW_HEIGHT)}>
            {span.durationUs === null ? (
                <span
                    className="absolute top-1/2 -translate-y-1/2 pl-1 text-[10px] font-bold uppercase tracking-wider text-zinc-500"
                    style={{ left: pct(span.offsetUs) }}
                >
                    {span.label}
                    {span.sublabel && <span className="ml-1.5 font-normal normal-case text-zinc-600">{span.sublabel}</span>}
                </span>
            ) : (
                <div
                    className={cn(
                        'absolute top-1/2 flex h-8 -translate-y-1/2 items-center rounded-md border backdrop-blur-sm',
                        span.color === 'teal'
                            ? 'border-emerald-500 bg-emerald-500/20 text-white dark:border-emerald-700 dark:bg-emerald-700/20'
                            : 'border-neutral-700 bg-neutral-800 text-white',
                    )}
                    style={{ left: pct(span.offsetUs), width: pct(span.durationUs), minWidth: '2px' }}
                >
                    <span className="shrink-0 px-2 text-[10px] uppercase tracking-wider">{span.label}</span>
                    <span className="ml-1.5 shrink-0 text-[10px] opacity-70">{formatDuration(span.durationUs)}</span>
                    {span.sublabel && <span className="ml-2 shrink-0 font-mono text-[10px] opacity-50">{span.sublabel}</span>}
                </div>
            )}
        </div>
    );
}

export function Timeline({ totalDurationUs, spans, className }: TimelineProps) {
    const [expandedIds, setExpandedIds] = useState<Set<string>>(
        () => new Set(allExpandableIds(spans)),
    );
    const [prevSpans, setPrevSpans] = useState(spans);
    const [cursor, setCursor] = useState<{ x: number; tooltipX: number; us: number } | null>(null);

    const { zoomLevel, barWidth, scrollRef, innerRef, ticksInnerRef, handleBarsScroll, handleTicksMouseDown, handleTicksDoubleClick } =
        useTimelineZoom();

    // Re-expand all nodes when spans are replaced (e.g. new data loaded)
    // Using the "derived state during render" pattern to avoid setState in an effect.
    if (prevSpans !== spans) {
        setPrevSpans(spans);
        setExpandedIds(new Set(allExpandableIds(spans)));
    }

    const ticks = useMemo(() => computeTicks(totalDurationUs), [totalDurationUs]);
    const tickStep = ticks[ticks.length - 1] - ticks[ticks.length - 2];
    // Extend the axis by half a tick step so the last bar isn't clipped at the edge
    const axisDurationUs = ticks[ticks.length - 1] + tickStep / 2;

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
            const scrollRect = scrollRef.current?.getBoundingClientRect();
            if (!rect || !scrollRect) return;
            const x = Math.max(0, e.clientX - rect.left);
            const tooltipX = Math.max(0, e.clientX - scrollRect.left);
            const us = (x / rect.width) * axisDurationUs;
            setCursor({ x, tooltipX, us });
        },
        [axisDurationUs, innerRef, scrollRef],
    );

    const handleMouseLeave = useCallback(() => setCursor(null), []);

    const flatSpans = useMemo(() => flattenSpans(spans, expandedIds), [spans, expandedIds]);

    const pct = useCallback((us: number) => `${(us / axisDurationUs) * 100}%`, [axisDurationUs]);

    return (
        <div
            className={cn(
                'rounded-lg border border-white/10 bg-surface font-mono text-xs overflow-clip contain-inline-size',
                className,
            )}
        >
            <ResizablePanelGroup orientation="horizontal" className="overflow-visible!">
                {/* ── Left label panel ─────────────────────────────────── */}
                <ResizablePanel defaultSize={160} minSize={160} className="overflow-visible!">
                    {/* Header */}
                    <div className={cn(STICKY, 'flex shrink-0 items-center border-b border-white/10 bg-surface px-3', ROW_HEIGHT)}>
                        <span className="font-sans text-xs font-semibold tracking-wide text-zinc-200">Timeline</span>
                    </div>

                    {flatSpans.map(({ span, depth, hasChildren }) => (
                        <TimelineLabelRow
                            key={span.id}
                            span={span}
                            depth={depth}
                            hasChildren={hasChildren}
                            isExpanded={expandedIds.has(span.id)}
                            onToggle={toggleExpand}
                        />
                    ))}
                </ResizablePanel>

                <ResizableHandle className="border-white/10 bg-white/10" />

                {/* ── Right timeline panel ──────────────────────────────── */}
                <ResizablePanel className="overflow-visible!">
                    <div className="overflow-x-clip">
                        {/* Ticks header — drag left/right to zoom, double-click to reset */}
                        <div
                            className={cn(STICKY, 'shrink-0 cursor-ew-resize overflow-hidden border-b border-white/10 bg-surface px-3 select-none', ROW_HEIGHT)}
                            onMouseDown={handleTicksMouseDown}
                            onDoubleClick={handleTicksDoubleClick}
                        >
                            <div className="relative h-full">
                                <div ref={ticksInnerRef} className="relative h-full" style={{ minWidth: barWidth }}>
                                    {ticks.map((us) => (
                                        <span
                                            key={us}
                                            className="absolute flex h-full flex-col items-center"
                                            style={{ left: pct(us), transform: 'translateX(-50%)' }}
                                        >
                                            <span className="px-1 py-0.5 text-[10px] text-zinc-600">{formatDuration(us)}</span>
                                            <span className="w-px flex-1 bg-white/10" />
                                        </span>
                                    ))}
                                </div>

                                {/* Zoom badge — absolute in the relative wrapper so it stays at the right edge while scrolling */}
                                {zoomLevel > 1 && (
                                    <span className="pointer-events-none absolute top-1/2 right-2 -translate-y-1/2 rounded bg-white/10 px-1.5 py-0.5 text-[10px] text-zinc-400">
                                        {zoomLevel.toFixed(1)}×
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Cursor tooltip — outside overflow-x-auto so sticky works against page scroll */}
                        {cursor !== null && (
                            <div className={CURSOR_STICKY}>
                                <div
                                    className="pointer-events-none absolute -translate-x-1/2 rounded bg-amber-400 px-1.5 py-0.5 text-[10px] font-bold text-black"
                                    style={{ left: `${cursor.tooltipX}px` }}
                                >
                                    {formatDuration(cursor.us)}
                                </div>
                            </div>
                        )}

                        {/* Bar rows */}
                        <div ref={scrollRef} className="overflow-x-auto px-3" onScroll={handleBarsScroll}>
                            <div
                                ref={innerRef}
                                className="relative"
                                style={{ minWidth: barWidth }}
                                onMouseMove={handleMouseMove}
                                onMouseLeave={handleMouseLeave}
                            >
                                {flatSpans.map(({ span }) => (
                                    <TimelineBarRow key={span.id} span={span} pct={pct} />
                                ))}

                                {/* Cursor line */}
                                {cursor !== null && (
                                    <div
                                        className="pointer-events-none absolute inset-y-0 w-px bg-amber-400/70"
                                        style={{ left: `${cursor.x}px` }}
                                    />
                                )}
                            </div>
                        </div>
                    </div>
                </ResizablePanel>
            </ResizablePanelGroup>
        </div>
    );
}
