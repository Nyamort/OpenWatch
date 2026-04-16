export interface ExceptionGraphBucket {
    bucket: string;
    handled: number;
    unhandled: number;
}

export interface ExceptionStats {
    count: number;
    handled: number;
    unhandled: number;
}

export interface ExceptionRow {
    group_key: string;
    class: string;
    message: string;
    handled: boolean;
    count: number;
    users: number;
    last_seen: string;
    first_seen: string;
}

export type ExceptionSortKey = 'last_seen' | 'class' | 'count' | 'users';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';

export interface ExceptionSummary {
    group_key: string;
    recorded_at: string;
    class: string;
    message: string;
    file: string;
    line: number;
    handled: boolean | number;
    code: string | null;
    php_version: string | null;
    laravel_version: string | null;
    trace: string;
    last_seen: string | null;
    first_seen: string | null;
    first_reported_in: string | null;
    impacted_users: number;
    occurrences: number;
    occurrences_7d: number;
    occurrences_24h: number;
    servers: number;
    [key: string]: unknown;
}
