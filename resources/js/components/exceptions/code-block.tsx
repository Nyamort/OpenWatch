import { cn } from '@/lib/utils';

export function CodeBlock({
    code,
    highlightedLine,
    className,
}: {
    code: Record<string, string>;
    highlightedLine: number;
    className?: string;
}) {
    const startLine = +Object.keys(code)[0];

    return (
        <div
            className={cn(
                'overflow-auto bg-neutral-50 font-mono text-[13px] dark:bg-neutral-900',
                className,
            )}
        >
            <table className="w-full border-collapse">
                <tbody>
                    {Object.entries(code).map(([lineNum, lineCode]) => {
                        const num = +lineNum;
                        const isHighlighted = num === highlightedLine;
                        const isEven = (num - startLine) % 2 === 0;

                        return (
                            <tr
                                key={num}
                                className={cn(
                                    isHighlighted
                                        ? 'bg-rose-100 dark:bg-rose-700/30'
                                        : isEven
                                          ? 'bg-white dark:bg-white/4'
                                          : 'dark:bg-white/2',
                                )}
                            >
                                <td className="w-12 px-4 py-1.5 text-right text-muted-foreground/60 select-none">
                                    {num}
                                </td>
                                <td className="px-4 py-1.5 whitespace-pre text-foreground">
                                    {lineCode}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
