export interface GraphBucket {
    bucket: string;
    authenticated_users: number;
    authenticated: number;
    guest: number;
}

export interface Stats {
    authenticated_users: number;
    authenticated_requests: number;
    guest_requests: number;
}

export interface UserRow {
    email: string;
    name: string | null;
    '2xx': number;
    '4xx': number;
    '5xx': number;
    request_count: number;
    job_count: number;
    exception_count: number;
    last_seen: string;
}

export type SortKey =
    | 'email'
    | '2xx'
    | '4xx'
    | '5xx'
    | 'request_count'
    | 'job_count'
    | 'exception_count'
    | 'last_seen';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
