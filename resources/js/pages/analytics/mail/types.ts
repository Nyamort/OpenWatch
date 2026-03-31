export interface MailGraphBucket {
    bucket: string;
    count: number;
    avg: number | null;
    p95: number | null;
}

export interface MailStats {
    count: number;
    avg: number | null;
    min: number | null;
    max: number | null;
    p95: number | null;
}

export interface MailRow {
    class: string;
    sample_id: number;
    count: number;
    avg: number | null;
    p95: number | null;
}

export type MailSortKey = 'mail' | 'count' | 'avg' | 'p95';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
