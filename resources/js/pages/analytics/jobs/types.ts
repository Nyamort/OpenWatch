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
    queued: number;
    processed: number;
    failed: number;
    released: number;
    avg: number | null;
    p95: number | null;
}

export type JobSortKey =
    | 'name'
    | 'total'
    | 'queued'
    | 'processed'
    | 'failed'
    | 'released'
    | 'avg'
    | 'p95';
export type SortDir = 'asc' | 'desc';

export interface JobTypeGraphBucket {
    bucket: string;
    processed: number;
    failed: number;
    released: number;
    avg: number | null;
    p95: number | null;
}

export interface JobTypeStats {
    count: number;
    processed: number;
    failed: number;
    released: number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

export interface JobAttemptRow {
    id: number;
    attempt_id: string;
    recorded_at: string;
    connection: string;
    queue: string;
    attempt: number;
    status: string;
    duration: number | null;
}

export type JobTypeSortKey = 'date' | 'attempt' | 'status' | 'duration';

export type { Pagination } from '@/types/analytics';
