import { Folder, FolderOpen } from 'lucide-react';
import { ChevronsDownUp, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { FilePath } from './file-path';
import { SourceLabel } from './source-label';
import type { IndexedTrace } from './types';

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

function VendorFrameRow({
    frame,
    previousFrame,
}: {
    frame: IndexedTrace;
    previousFrame: IndexedTrace | undefined;
}) {
    return (
        <div className="flex h-11 items-center justify-between gap-6 px-3">
            {previousFrame ? (
                <SourceLabel
                    source={previousFrame.source}
                    className="text-xs"
                />
            ) : (
                <span className="font-mono text-xs text-muted-foreground">
                    Entrypoint
                </span>
            )}
            <FilePath file={frame.file} className="text-xs" />
        </div>
    );
}

export function VendorFrameGroup({
    frames,
    trace,
}: {
    frames: IndexedTrace[];
    trace: IndexedTrace[];
}) {
    const [expanded, setExpanded] = useState(false);
    const FolderIcon = expanded ? FolderOpen : Folder;

    return (
        <div
            className={cn(
                'group rounded-lg border',
                expanded
                    ? 'bg-white dark:border-white/5 dark:bg-white/5'
                    : 'border-dashed border-neutral-300 bg-neutral-50 opacity-90 dark:border-white/10 dark:bg-white/1',
            )}
        >
            <div
                className="flex h-11 cursor-pointer items-center gap-2.5 rounded-lg pr-2.5 pl-4 hover:bg-white/50 dark:hover:bg-white/2"
                onClick={() => setExpanded(!expanded)}
            >
                <FolderIcon
                    className={cn(
                        'size-3 shrink-0',
                        expanded
                            ? 'text-emerald-500'
                            : 'text-muted-foreground group-hover:text-emerald-500',
                    )}
                />
                <span className="flex-1 font-mono text-xs text-muted-foreground">
                    {frames.length.toLocaleString()} vendor{' '}
                    {frames.length === 1 ? 'frame' : 'frames'}
                </span>
                <ExpandToggle
                    expanded={expanded}
                    onClick={(e) => {
                        e.stopPropagation();
                        setExpanded(!expanded);
                    }}
                    className="group-hover:text-emerald-500"
                />
            </div>

            {expanded && (
                <div className="flex flex-col divide-y divide-neutral-100 border-t border-neutral-100 dark:divide-white/5 dark:border-white/5">
                    {frames.map((frame, i) => (
                        <VendorFrameRow
                            key={i}
                            frame={frame}
                            previousFrame={trace[frame.index + 1]}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
