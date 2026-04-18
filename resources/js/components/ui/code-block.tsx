import { Check, Copy } from 'lucide-react';
import { useMemo } from 'react';
import { PrismLight as SyntaxHighlighter } from 'react-syntax-highlighter';
import bash from 'react-syntax-highlighter/dist/esm/languages/prism/bash';
import json from 'react-syntax-highlighter/dist/esm/languages/prism/json';
import php from 'react-syntax-highlighter/dist/esm/languages/prism/php';
import sql from 'react-syntax-highlighter/dist/esm/languages/prism/sql';
import {
    vs,
    vscDarkPlus,
} from 'react-syntax-highlighter/dist/esm/styles/prism';
import { useAppearance } from '@/hooks/use-appearance';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';

SyntaxHighlighter.registerLanguage('bash', bash);
SyntaxHighlighter.registerLanguage('json', json);
SyntaxHighlighter.registerLanguage('php', php);
SyntaxHighlighter.registerLanguage('sql', sql);

type Language = 'bash' | 'json' | 'php' | 'sql' | 'text';

interface CodeBlockProps {
    code: string;
    language?: Language;
    copyable?: boolean;
    copyValue?: string;
    wrapLongLines?: boolean;
    showLineNumbers?: boolean;
    highlightedLine?: number;
    startingLineNumber?: number;
    fontSize?: number;
    className?: string;
}

export function CodeBlock({
    code,
    language = 'text',
    copyable = false,
    copyValue,
    wrapLongLines = true,
    showLineNumbers = false,
    highlightedLine,
    startingLineNumber = 1,
    fontSize = 12,
    className,
}: CodeBlockProps) {
    const { resolvedAppearance } = useAppearance();
    const [copiedText, copy] = useClipboard();

    const isDark = resolvedAppearance === 'dark';
    const prismStyle = isDark ? vscDarkPlus : vs;

    const customStyle = useMemo(
        () => ({
            fontSize,
            padding: 0,
            margin: 0,
            lineHeight: 1.6,
            background: 'none',
            border: 'none',
        }),
        [fontSize],
    );

    const wrapLines = highlightedLine !== undefined;
    const lineProps = (lineNumber: number) => {
        const style: React.CSSProperties = { display: 'block' };
        if (highlightedLine !== undefined && lineNumber === highlightedLine) {
            style.backgroundColor = isDark
                ? 'rgb(190 18 60 / 0.3)'
                : 'rgb(254 205 211)';
        }
        return { style };
    };

    const highlighter = (
        <SyntaxHighlighter
            language={language === 'text' ? 'plaintext' : language}
            style={prismStyle}
            customStyle={customStyle}
            wrapLongLines={wrapLongLines}
            wrapLines={wrapLines}
            lineProps={wrapLines ? lineProps : undefined}
            showLineNumbers={showLineNumbers}
            startingLineNumber={startingLineNumber}
            codeTagProps={{
                style: { background: 'none', fontFamily: 'inherit' },
            }}
        >
            {code}
        </SyntaxHighlighter>
    );

    if (!copyable) {
        return <div className={className}>{highlighter}</div>;
    }

    return (
        <div className={cn('flex min-w-0 items-start', className)}>
            <div className="scrollbar-none min-w-0 flex-1 overflow-x-auto pr-2">
                {highlighter}
            </div>
            <button
                type="button"
                onClick={() => copy(copyValue ?? code)}
                className="shrink-0 text-muted-foreground transition-colors hover:text-foreground"
                title="Copy"
            >
                {copiedText !== null ? (
                    <Check className="size-4 text-emerald-500" />
                ) : (
                    <Copy className="size-4" />
                )}
            </button>
        </div>
    );
}
