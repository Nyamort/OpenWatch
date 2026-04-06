import type { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}
export function formatDuration(us: number | null) {
    if (us == null) return `—`;
    if (us < 0)
        throw new Error(
            'Negative numbers are unsupported in formatDuration',
        );
    if (us === 0 ) return `${us}ms`;
    if (us < 1e3) return `${us}μs`;
    if (us < 1e6) return `${(us / 1e3).toFixed(2)}ms`;

    let seconds = us / 1e6;
    if (seconds < 60) return `${seconds.toFixed(2)}s`;

    let minutes = Math.floor(seconds / 60);
    seconds = Math.round(seconds % 60);
    if (minutes < 60)
        return seconds > 0
            ? `${minutes} ${minutes > 1 ? 'mins' : 'min'} ${seconds}s`
            : `${minutes} ${minutes > 1 ? 'mins' : 'min'}`;

    let hours = Math.floor(minutes / 60);
    minutes = Math.round(minutes % 60);
    if (hours < 24)
        return minutes > 0
            ? `${hours} ${hours > 1 ? 'hrs' : 'hr'} ${minutes} ${minutes > 1 ? 'mins' : 'min'}`
            : `${hours} ${hours > 1 ? 'hrs' : 'hr'}`;

    const days = Math.floor(hours / 24);
    hours = Math.round(hours % 24);
    return hours > 0
        ? `${days} ${days > 1 ? 'days' : 'day'} ${hours} ${hours > 1 ? 'hrs' : 'hr'}`
        : `${days} ${days > 1 ? 'days' : 'day'}`;
}
