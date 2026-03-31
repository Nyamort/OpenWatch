export interface CacheEventsGraphBucket {
    bucket: string;
    hits: number;
    misses: number;
    writes: number;
    deletes: number;
}

export interface CacheFailuresGraphBucket {
    bucket: string;
    write_failures: number;
    delete_failures: number;
}

export interface CacheStats {
    total: number;
    hits: number;
    misses: number;
    writes: number;
    deletes: number;
    failures: number;
    write_failures: number;
    delete_failures: number;
}

export interface CacheKeyRow {
    key: string;
    hit_pct: number | null;
    hits: number;
    misses: number;
    writes: number;
    deletes: number;
    failures: number;
    total: number;
}

export type CacheSortKey =
    | 'key'
    | 'hit_pct'
    | 'hits'
    | 'misses'
    | 'writes'
    | 'deletes'
    | 'failures'
    | 'total';
export type SortDir = 'asc' | 'desc';

export type { Pagination } from '@/types/analytics';
