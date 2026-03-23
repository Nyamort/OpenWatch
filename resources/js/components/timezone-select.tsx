import { Check, ChevronsUpDown, Search } from 'lucide-react';
import { useRef, useState } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface TimezoneSelectProps {
    timezones: string[];
    value: string;
    onChange: (tz: string) => void;
}

export function TimezoneSelect({ timezones, value, onChange }: TimezoneSelectProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const searchRef = useRef<HTMLInputElement>(null);

    const filtered = search.trim()
        ? timezones.filter((tz) => tz.toLowerCase().includes(search.toLowerCase()))
        : timezones;

    function handleOpenChange(next: boolean) {
        setOpen(next);
        if (!next) {
            setSearch('');
        } else {
            setTimeout(() => searchRef.current?.focus(), 50);
        }
    }

    return (
        <DropdownMenu open={open} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <button className="flex w-full items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <span className="flex-1 truncate text-left">{value || 'Select timezone'}</span>
                    <ChevronsUpDown className="size-3.5 shrink-0 text-muted-foreground" />
                </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                className="flex w-72 flex-col p-0 shadow-lg"
                align="start"
                sideOffset={4}
                onCloseAutoFocus={(e) => e.preventDefault()}
            >
                <div className="flex items-center gap-2 border-b border-border px-3 py-2">
                    <Search className="size-3.5 shrink-0 text-muted-foreground" />
                    <input
                        ref={searchRef}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Find timezone..."
                        className="flex-1 bg-transparent text-xs outline-none placeholder:text-muted-foreground"
                    />
                </div>

                <div className="max-h-60 overflow-y-auto py-1">
                    {filtered.length === 0 ? (
                        <p className="px-3 py-3 text-center text-xs text-muted-foreground">
                            No results
                        </p>
                    ) : (
                        filtered.map((tz) => (
                            <button
                                key={tz}
                                onClick={() => {
                                    onChange(tz);
                                    setOpen(false);
                                    setSearch('');
                                }}
                                className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm transition-colors hover:bg-accent"
                            >
                                <span className="flex-1 truncate">{tz}</span>
                                {tz === value && <Check className="size-3.5 shrink-0 text-primary" />}
                            </button>
                        ))
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
