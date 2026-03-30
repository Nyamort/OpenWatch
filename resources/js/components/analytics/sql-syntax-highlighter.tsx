import { useMemo } from 'react';
import { PrismLight as SyntaxHighlighter } from 'react-syntax-highlighter';
import sql from 'react-syntax-highlighter/dist/esm/languages/prism/sql';
import { vscDarkPlus, vs } from 'react-syntax-highlighter/dist/esm/styles/prism';
import { useAppearance } from '@/hooks/use-appearance';

SyntaxHighlighter.registerLanguage('sql', sql);

const THEMES = {
    light: vs,
    dark: vscDarkPlus,
};

interface SqlSyntaxHighlighterProps {
    children: string;
    className?: string;
    wrapLongLines?: boolean;
}

export default function SqlSyntaxHighlighter({ children, className = '', wrapLongLines = true }: SqlSyntaxHighlighterProps) {
    const { resolvedAppearance } = useAppearance();

    const customStyle = useMemo(() => ({
        fontSize: 12,
        padding: 0,
        margin: 0,
        lineHeight: 2,
        background: 'none',
        border: 'none',
        overflow: 'hidden',
        textOverflow: 'ellipsis',
        whiteSpace: 'nowrap' as const,
    }), []);

    return (
        <div className={className}>
            <SyntaxHighlighter
                language="sql"
                style={THEMES[resolvedAppearance]}
                customStyle={customStyle}
                wrapLongLines={wrapLongLines}
                codeTagProps={{ style: { background: 'none' } }}
            >
                {children}
            </SyntaxHighlighter>
        </div>
    );
}
