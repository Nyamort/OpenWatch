import { ChevronsDownUp, ChevronsUpDown, Info } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { CodeBlock } from './code-block';
import { FilePath } from './file-path';
import { SourceLabel } from './source-label';
import type { IndexedTrace } from './types';
import { splitFileLine } from './utils';

function ExpandToggle({
    expanded,
    className,
    ...rest
}: React.ButtonHTMLAttributes<HTMLButtonElement> & { expanded: boolean }) {
    const Icon = expanded ? ChevronsDownUp : ChevronsUpDown;

    return (
        <button
            type="button"
            className={cn(
                'flex size-6 items-center justify-center rounded-md border bg-white dark:border-white/8',
                expanded
                    ? 'text-emerald-500 dark:bg-white/5'
                    : 'text-muted-foreground dark:bg-white/3',
                className,
            )}
            {...rest}
        >
            <Icon className="size-3" />
        </button>
    );
}

export function AppFrame({
    frame,
    previousFrame,
    defaultExpanded = false,
    frameCountWithCode,
}: {
    frame: IndexedTrace;
    previousFrame: IndexedTrace | undefined;
    defaultExpanded?: boolean;
    frameCountWithCode: number;
}) {
    const [expanded, setExpanded] = useState(defaultExpanded);
    const [, lineNumber] = splitFileLine(frame.file);
    const hasCode = !!frame.code && Object.keys(frame.code).length > 0;

    return (
        <div
            className={cn(
                'overflow-hidden rounded-lg border',
                expanded ? 'dark:border-white/10' : 'dark:border-white/5',
            )}
        >
            <div
                className={cn(
                    'flex h-11 items-center gap-2.5 bg-white pr-2.5 pl-4 dark:bg-white/3',
                    expanded && 'dark:bg-white/5',
                    hasCode &&
                        'cursor-pointer hover:bg-neutral-50 dark:hover:bg-white/5',
                )}
                onClick={hasCode ? () => setExpanded(!expanded) : undefined}
            >
                <div className="flex size-3 items-center justify-center">
                    <div
                        className={cn(
                            'size-2 rounded-full',
                            expanded
                                ? 'bg-neutral-400'
                                : 'bg-neutral-300 dark:bg-neutral-700',
                        )}
                    />
                </div>

                <div className="flex flex-1 items-center justify-between gap-6 overflow-hidden">
                    {previousFrame ? (
                        <SourceLabel
                            source={previousFrame.source}
                            className="text-[13px]"
                        />
                    ) : (
                        <span className="font-mono text-[13px] text-muted-foreground">
                            Entrypoint
                        </span>
                    )}
                    <FilePath file={frame.file} className="text-xs" />
                </div>

                {frameCountWithCode > 0 &&
                    (hasCode ? (
                        <ExpandToggle
                            expanded={expanded}
                            onClick={(e) => {
                                e.stopPropagation();
                                setExpanded(!expanded);
                            }}
                        />
                    ) : (
                        <span
                            className="flex size-6 items-center justify-center text-muted-foreground/40"
                            title={`Source code limited to first ${frameCountWithCode} frames`}
                        >
                            <Info className="size-4" />
                        </span>
                    ))}
            </div>

            {hasCode && expanded && (
                <CodeBlock
                    code={frame.code!}
                    highlightedLine={lineNumber ? +lineNumber : 0}
                    className="border-t border-neutral-100 dark:border-white/10"
                />
            )}
        </div>
    );
}
