export interface QueryGraphBucket {
    bucket: string;
    calls: number;
    avg: number | null;
    p95: number | null;
}

export interface QueryStats {
    count: number;
    avg: number | null;
    min: number | null;
    max: number | null;
    p95: number | null;
}

export interface QueryRow {
    sql_hash: string;
    query: string;
    connection: string;
    calls: number;
    total: number | null;
    avg: number | null;
    p95: number | null;
}

export type QuerySortKey = 'query' | 'connection' | 'calls' | 'total' | 'avg' | 'p95';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
