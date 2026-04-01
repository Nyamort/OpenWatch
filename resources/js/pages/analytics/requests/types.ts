export interface GraphBucket {
    bucket: string;
    count: number;
    '2xx': number;
    '4xx': number;
    '5xx': number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

export interface Stats {
    count: number;
    '2xx': number;
    '4xx': number;
    '5xx': number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

export interface PathRow {
    methods: string[];
    path: string | null;
    '2xx': number;
    '4xx': number;
    '5xx': number;
    total: number;
    avg: number | null;
    p95: number | null;
}

export type SortKey =
    | 'method'
    | 'path'
    | '2xx'
    | '4xx'
    | '5xx'
    | 'total'
    | 'avg'
    | 'p95';
export type SortDir = 'asc' | 'desc';

export interface RouteRequestRow {
    id: number;
    recorded_at: string;
    method: string;
    url: string;
    status_code: number;
    duration: number | null;
    exceptions: number;
    queries: number;
}

export type RouteSortKey = 'date' | 'status' | 'duration';

export type { Pagination } from '@/types/analytics';
