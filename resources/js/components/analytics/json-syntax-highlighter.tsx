import { useMemo } from 'react';
import { PrismLight as SyntaxHighlighter } from 'react-syntax-highlighter';
import json from 'react-syntax-highlighter/dist/esm/languages/prism/json';
import {
    vscDarkPlus,
    vs,
} from 'react-syntax-highlighter/dist/esm/styles/prism';
import { useAppearance } from '@/hooks/use-appearance';

SyntaxHighlighter.registerLanguage('json', json);

const THEMES = {
    light: vs,
    dark: vscDarkPlus,
};

interface JsonSyntaxHighlighterProps {
    children: string;
    className?: string;
}

export default function JsonSyntaxHighlighter({
    children,
    className = '',
}: JsonSyntaxHighlighterProps) {
    const { resolvedAppearance } = useAppearance();

    const customStyle = useMemo(
        () => ({
            fontSize: 12,
            padding: 0,
            margin: 0,
            lineHeight: 1.6,
            background: 'none',
            border: 'none',
        }),
        [],
    );

    return (
        <div className={className}>
            <SyntaxHighlighter
                language="json"
                style={THEMES[resolvedAppearance]}
                customStyle={customStyle}
                wrapLongLines
                codeTagProps={{ style: { background: 'none' } }}
            >
                {children}
            </SyntaxHighlighter>
        </div>
    );
}
