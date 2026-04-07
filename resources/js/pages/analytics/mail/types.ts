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

export interface MailTypeGraphBucket {
    bucket: string;
    count: number;
    avg: number | null;
    p95: number | null;
}

export interface MailTypeStats {
    count: number;
    min: number | null;
    max: number | null;
    avg: number | null;
    p95: number | null;
}

export interface MailRunRow {
    id: string;
    recorded_at: string;
    source: string | null;
    source_preview: string | null;
    mailer: string;
    subject: string | null;
    to: number;
    cc: number;
    bcc: number;
    attachments: number;
    duration: number | null;
}

export type MailTypeSortKey = 'date' | 'duration';

export type { Pagination } from '@/types/analytics';
