export interface JobGraphBucket {
    bucket: string;
    count: number;
    processed: number;
    failed: number;
    released: number;
    avg: number | null;
    p95: number | null;
}

export interface JobStats {
    count: number;
    processed: number;
    failed: number;
    released: number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

export interface JobRow {
    name: string | null;
    total: number;
    processed: number;
    failed: number;
    released: number;
    avg: number | null;
    p95: number | null;
}

export type JobSortKey = 'name' | 'total' | 'processed' | 'failed' | 'released' | 'avg' | 'p95';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
