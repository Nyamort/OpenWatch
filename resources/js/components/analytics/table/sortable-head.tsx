import { ChevronDown, ChevronUp, ChevronsUpDown } from 'lucide-react';
import { TableHead } from '@/components/ui/table';

interface SortableHeadProps {
    column: string;
    sort: string;
    direction: 'asc' | 'desc';
    onSort: (column: string) => void;
    align?: 'left' | 'right';
    children: React.ReactNode;
    className?: string;
}

function SortIcon({
    column,
    sort,
    direction,
}: {
    column: string;
    sort: string;
    direction: 'asc' | 'desc';
}) {
    if (sort !== column)
        return <ChevronsUpDown className="size-3 opacity-40" />;
    return direction === 'asc' ? (
        <ChevronUp className="size-3" />
    ) : (
        <ChevronDown className="size-3" />
    );
}

export function colClass(
    column: string,
    sort: string,
    align: 'left' | 'right' = 'left',
) {
    const active =
        sort === column ? 'text-foreground' : 'text-muted-foreground';
    const base =
        'flex cursor-pointer items-center gap-1 uppercase tracking-wide hover:text-foreground';
    return align === 'right'
        ? `${base} w-full justify-end ${active}`
        : `${base} ${active}`;
}

export function SortableHead({
    column,
    sort,
    direction,
    onSort,
    align = 'left',
    children,
    className,
}: SortableHeadProps) {
    return (
        <TableHead className={className}>
            <button
                onClick={() => onSort(column)}
                className={colClass(column, sort, align)}
            >
                {align === 'right' && (
                    <SortIcon
                        column={column}
                        sort={sort}
                        direction={direction}
                    />
                )}
                {children}
                {align === 'left' && (
                    <SortIcon
                        column={column}
                        sort={sort}
                        direction={direction}
                    />
                )}
            </button>
        </TableHead>
    );
}
