import { Check, Copy } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';
import { AppFrame } from './app-frame';
import { FilePath } from './file-path';
import type { ExceptionOccurrence, FrameGroup, IndexedTrace } from './types';
import { buildMarkdown } from './utils';
import { VendorFrameGroup } from './vendor-frame-group';

interface Props {
    exception: ExceptionOccurrence;
    className?: string;
}

export default function ExceptionCard({ exception, className }: Props) {
    const [, copy] = useClipboard(2000);
    const [isCopied, setIsCopied] = useState(false);

    const normalisedTrace = useMemo<IndexedTrace[]>(() => {
        const trace: IndexedTrace[] = exception.trace.map((f, i) => ({
            ...f,
            index: i,
        }));

        const originKey = `${exception.file}:${exception.line}`;
        const originMissing =
            trace[0]?.source !== '' && !trace.some((f) => f.file === originKey);

        if (originMissing) {
            trace.unshift({
                file: originKey,
                source: '',
                code: null,
                index: -1,
            });
            trace.forEach((f, i) => {
                f.index = i;
            });
        }

        return trace;
    }, [exception]);

    const frameGroups = useMemo<FrameGroup[]>(() => {
        const groups: FrameGroup[] = [];
        let current: FrameGroup | null = null;

        normalisedTrace.forEach((frame) => {
            const isVendor =
                frame.file.startsWith('vendor/') || frame.file.startsWith('[');

            if (current === null || isVendor !== current.vendor) {
                current = { vendor: isVendor, frames: [] };
                groups.push(current);
            }

            current.frames.push(frame);
        });

        return groups;
    }, [normalisedTrace]);

    const firstCodeFrameIndex = normalisedTrace.findIndex((f) => f.code);
    const framesWithCodeCount = normalisedTrace.filter((f) => f.code).length;

    async function copyAsMarkdown() {
        const success = await copy(buildMarkdown(exception));
        if (success) {
            setIsCopied(true);
            setTimeout(() => setIsCopied(false), 2000);
        }
    }

    const CopyIcon = isCopied ? Check : Copy;

    return (
        <div
            className={cn(
                'overflow-hidden rounded-xl border bg-white dark:bg-neutral-900',
                className,
            )}
        >
            {/* Header */}
            <div className="flex flex-col gap-3 p-4 md:p-5">
                <div className="flex justify-between gap-2 max-md:flex-col md:items-center">
                    <div className="flex items-center gap-2">
                        <Badge
                            variant={
                                exception.handled ? 'warning' : 'destructive'
                            }
                        >
                            {exception.handled ? 'Handled' : 'Unhandled'}
                        </Badge>
                        {exception.code !== '0' && (
                            <Badge variant="outline">{exception.code}</Badge>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            className="flex items-center gap-1.5 rounded-md border bg-white px-2.5 py-1 text-xs font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                            onClick={copyAsMarkdown}
                            disabled={isCopied}
                        >
                            <CopyIcon
                                className={cn(
                                    'size-3.5 transition-colors',
                                    isCopied && 'text-emerald-500',
                                )}
                            />
                            {isCopied ? 'Copied!' : 'Copy as Markdown'}
                        </button>

                        <div className="flex h-6 shrink-0 items-center divide-x divide-neutral-200 rounded-md border border-neutral-200 bg-white font-mono text-xs dark:divide-neutral-700 dark:border-neutral-700 dark:bg-neutral-800">
                            <div className="flex items-center gap-1.5 px-2 py-0.5">
                                <span className="text-muted-foreground uppercase">
                                    Laravel
                                </span>
                                <span>{exception.laravel_version}</span>
                            </div>
                            <div className="flex items-center gap-1.5 px-2 py-0.5">
                                <span className="text-muted-foreground uppercase">
                                    PHP
                                </span>
                                <span>{exception.php_version}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <p className="mt-1 text-2xl/none font-semibold break-all dark:text-neutral-200">
                    {exception.class}
                </p>

                <p className="relative max-h-48 overflow-y-auto tracking-tight text-pretty whitespace-pre-wrap text-muted-foreground">
                    {exception.message}
                </p>
            </div>

            {/* Stack trace */}
            <div className="flex flex-col gap-1.5 border-t bg-neutral-50 p-2.5 dark:border-neutral-800 dark:bg-neutral-900">
                {frameGroups.length > 0 ? (
                    frameGroups.map((group, groupIndex) =>
                        group.vendor ? (
                            <VendorFrameGroup
                                key={groupIndex}
                                frames={group.frames}
                                trace={normalisedTrace}
                            />
                        ) : (
                            group.frames.map((frame, frameIndex) => (
                                <AppFrame
                                    key={frameIndex}
                                    frame={frame}
                                    previousFrame={
                                        normalisedTrace[frame.index + 1]
                                    }
                                    defaultExpanded={
                                        frame.index === firstCodeFrameIndex
                                    }
                                    frameCountWithCode={framesWithCodeCount}
                                />
                            ))
                        ),
                    )
                ) : (
                    <div className="flex h-11 items-center justify-between gap-6 overflow-hidden rounded-lg border bg-white pr-2.5 pl-4 dark:border-white/10 dark:bg-white/3">
                        <span className="truncate font-mono text-[13px] text-muted-foreground">
                            Stack trace not available
                        </span>
                        <FilePath
                            file={`${exception.file}:${exception.line}`}
                            className="text-xs"
                        />
                    </div>
                )}
            </div>
        </div>
    );
}
