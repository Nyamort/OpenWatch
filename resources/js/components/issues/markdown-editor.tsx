import { useState } from 'react';
import CodeMirror from '@uiw/react-codemirror';
import { markdown } from '@codemirror/lang-markdown';
import { oneDark } from '@codemirror/theme-one-dark';
import { marked } from 'marked';
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
            <div className="min-h-48">
                {tab === 'write' ? (
                    <CodeMirror
                        value={value}
                        onChange={onChange}
                        extensions={[markdown()]}
                        theme={oneDark}
                        basicSetup={{ lineNumbers: false, foldGutter: false }}
                        className="text-sm [&_.cm-editor]:rounded-b-xl [&_.cm-editor]:border-0 [&_.cm-scroller]:min-h-48 [&_.cm-scroller]:p-4 [&_.cm-focused]:outline-none"
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
