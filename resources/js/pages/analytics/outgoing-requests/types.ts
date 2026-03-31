export interface OutgoingRequestGraphBucket {
    bucket: string;
    success: number;
    count_4xx: number;
    count_5xx: number;
    avg: number | null;
    p95: number | null;
}

export interface OutgoingRequestStats {
    total: number;
    success: number;
    count_4xx: number;
    count_5xx: number;
    avg: number | null;
    min: number | null;
    max: number | null;
    p95: number | null;
}

export interface OutgoingRequestHostRow {
    host: string;
    success: number;
    count_4xx: number;
    count_5xx: number;
    total: number;
    avg: number | null;
    p95: number | null;
}

export type OutgoingRequestSortKey =
    | 'host'
    | 'success'
    | 'count_4xx'
    | 'count_5xx'
    | 'total'
    | 'avg'
    | 'p95';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
