import { marked } from 'marked';
import { useState } from 'react';
import { cn } from '@/lib/utils';

interface Props {
    value: string;
    onChange: (value: string) => void;
}

type Tab = 'write' | 'preview';

export function MarkdownEditor({ value, onChange }: Props) {
    const [tab, setTab] = useState<Tab>('write');

    return (
        <div className="rounded-xl border bg-card text-card-foreground shadow-sm">
            {/* Header */}
            <div className="flex items-center gap-1 border-b px-4 pt-3">
                {(['write', 'preview'] as Tab[]).map((t) => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={cn(
                            '-mb-px px-3 py-2 text-sm font-medium capitalize transition-colors',
                            tab === t
                                ? 'border-b-2 border-foreground text-foreground'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {t === 'write' ? 'Write' : 'Preview'}
                    </button>
                ))}
            </div>

            {/* Content */}
            <div>
                {tab === 'write' ? (
                    <textarea
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        className="w-full resize-y rounded-b-xl bg-transparent p-4 text-sm outline-none placeholder:text-muted-foreground"
                        placeholder="Enter a description…"
                    />
                ) : (
                    <div
                        className="prose prose-sm dark:prose-invert max-w-none p-4"
                        dangerouslySetInnerHTML={{
                            __html: marked(value) as string,
                        }}
                    />
                )}
            </div>
        </div>
    );
}
