export interface LogRow {
    id: string;
    recorded_at: string;
    source: string | null;
    source_preview: string | null;
    level: string;
    message: string;
    context: string | null;
}

export type { Pagination } from '@/types/analytics';
