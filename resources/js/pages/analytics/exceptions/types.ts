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
    count: number;
    users: number;
    last_seen: string;
    first_seen: string;
}

export type ExceptionSortKey = 'last_seen' | 'class' | 'count' | 'users';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
