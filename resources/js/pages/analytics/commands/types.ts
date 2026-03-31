export interface CommandGraphBucket {
    bucket: string;
    count: number;
    successful: number;
    failed: number;
    avg: number | null;
    p95: number | null;
}

export interface CommandStats {
    count: number;
    successful: number;
    failed: number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

export interface CommandRow {
    name: string | null;
    total: number;
    successful: number;
    failed: number;
    avg: number | null;
    p95: number | null;
}

export type CommandSortKey =
    | 'name'
    | 'total'
    | 'successful'
    | 'failed'
    | 'avg'
    | 'p95';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
