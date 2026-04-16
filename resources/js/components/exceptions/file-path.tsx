import { cn } from '@/lib/utils';
import { splitFileLine } from './utils';

export function FilePath({
    file,
    className,
}: {
    file: string;
    className?: string;
}) {
    const [filePath, lineNumber] = splitFileLine(file);

    return (
        <span
            className={cn(
                'flex min-w-0 font-mono text-muted-foreground',
                className,
            )}
        >
            <span dir="rtl" className="truncate">
                <span dir="ltr">{filePath}</span>
            </span>
            {lineNumber && (
                <span className="shrink-0 text-muted-foreground/60">
                    :{lineNumber}
                </span>
            )}
        </span>
    );
}
