import { Search } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Input } from '@/components/ui/input';

interface AnalyticsTableHeaderProps {
    icon: LucideIcon;
    label: string;
    count: number;
    search: string;
    searchPlaceholder?: string;
    onSearch: (value: string) => void;
}

export function AnalyticsTableHeader({ icon: Icon, label, count, search, searchPlaceholder = 'Search...', onSearch }: AnalyticsTableHeaderProps) {
    return (
        <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 text-sm font-medium">
                <Icon className="size-4 text-muted-foreground" />
                <span>{count} {label}</span>
            </div>
            <div className="relative w-64">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    placeholder={searchPlaceholder}
                    value={search}
                    onChange={(e) => onSearch(e.target.value)}
                    className="h-8 pl-8 text-sm"
                />
            </div>
        </div>
    );
}
