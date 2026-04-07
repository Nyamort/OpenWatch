export interface NotificationGraphBucket {
    bucket: string;
    count: number;
    avg: number | null;
    p95: number | null;
}

export interface NotificationStats {
    count: number;
    avg: number | null;
    min: number | null;
    max: number | null;
    p95: number | null;
}

export interface NotificationRow {
    class: string;
    sample_id: number;
    count: number;
    avg: number | null;
    p95: number | null;
}

export type NotificationSortKey = 'notification' | 'count' | 'avg' | 'p95';
export type SortDir = 'asc' | 'desc';

export interface NotificationTypeGraphBucket {
    bucket: string;
    count: number;
    avg: number | null;
    p95: number | null;
}

export interface NotificationTypeStats {
    count: number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

export interface NotificationRunRow {
    id: string;
    recorded_at: string;
    source: string | null;
    source_preview: string | null;
    channel: string;
    duration: number | null;
}

export type NotificationTypeSortKey = 'date' | 'duration';

export type { Pagination } from '@/types/analytics';
