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

export interface CommandTypeGraphBucket {
    bucket: string;
    successful: number;
    failed: number;
    avg: number | null;
    p95: number | null;
}

export interface CommandTypeStats {
    count: number;
    successful: number;
    failed: number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

export interface CommandRunRow {
    id: string;
    recorded_at: string;
    command: string | null;
    exit_code: number | null;
    duration: number | null;
}

export type CommandTypeSortKey = 'date' | 'exit_code' | 'duration';

export type { Pagination } from '@/types/analytics';
