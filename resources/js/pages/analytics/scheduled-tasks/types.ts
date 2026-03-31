export interface ScheduledTaskGraphBucket {
    bucket: string;
    processed: number;
    skipped: number;
    failed: number;
    avg: number | null;
    p95: number | null;
}

export interface ScheduledTaskStats {
    count: number;
    processed: number;
    skipped: number;
    failed: number;
    avg: number | null;
    p95: number | null;
}

export interface ScheduledTaskRow {
    name: string | null;
    cron: string | null;
    next_run: string | null;
    total: number;
    processed: number;
    skipped: number;
    failed: number;
    avg: number | null;
    p95: number | null;
}

export type ScheduledTaskSortKey =
    | 'task'
    | 'processed'
    | 'skipped'
    | 'failed'
    | 'total'
    | 'avg'
    | 'p95';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
