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

export interface OutgoingRequestRunRow {
    id: string;
    recorded_at: string;
    source: string | null;
    source_preview: string | null;
    method: string | null;
    status_code: number | null;
    url: string | null;
    duration: number | null;
}

export type OutgoingRequestHostSortKey = 'date' | 'duration' | 'status';

export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
