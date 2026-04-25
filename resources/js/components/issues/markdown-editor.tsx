import { marked } from 'marked';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

interface Props {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
}

type Tab = 'write' | 'preview';

export function MarkdownEditor({ value, onChange, placeholder = 'Enter a description…' }: Props) {
    const [tab, setTab] = useState<Tab>('write');
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    useEffect(() => {
        const el = textareaRef.current;
        if (!el) {
            return;
        }
        el.style.height = 'auto';
        el.style.height = `${el.scrollHeight}px`;
    }, [value, tab]);

    return (
        <div className="rounded-xl border bg-transparent text-card-foreground shadow-sm">
            {/* Header */}
            <div className="flex items-center gap-1 border-b px-4 pt-3">
                {(['write', 'preview'] as Tab[]).map((t) => (
                    <button
                        key={t}
                        type="button"
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
                        ref={textareaRef}
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        className="w-full resize-none overflow-hidden rounded-b-xl bg-transparent p-4 text-sm outline-none placeholder:text-muted-foreground"
                        placeholder={placeholder}
                        rows={1}
                    />
                ) : (
                    <div
                        className="prose prose-sm max-w-none p-4 dark:prose-invert"
                        dangerouslySetInnerHTML={{
                            __html: marked(value) as string,
                        }}
                    />
                )}
            </div>
        </div>
    );
}
