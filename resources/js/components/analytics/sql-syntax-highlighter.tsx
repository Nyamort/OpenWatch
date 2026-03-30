import { memo, useEffect, useMemo, useRef, useState } from 'react';
import { PrismLight as SyntaxHighlighter } from 'react-syntax-highlighter';
import sql from 'react-syntax-highlighter/dist/esm/languages/prism/sql';
import { oneDark, oneLight } from 'react-syntax-highlighter/dist/esm/styles/prism';
import { useDarkMode } from 'usehooks-ts';

SyntaxHighlighter.registerLanguage('sql', sql);

const THEMES = {
    light: oneLight,
    dark: oneDark,
};

function hasDarkParent(el: HTMLElement | null): boolean {
    if (!el) return false;
    if (el.classList.contains('dark')) return true;
    return hasDarkParent(el.parentElement);
}

interface SqlSyntaxHighlighterProps {
    children: string;
    className?: string;
    wrapLongLines?: boolean;
}

function SqlSyntaxHighlighter({ children, className = '', wrapLongLines = true }: SqlSyntaxHighlighterProps) {
    const { isDarkMode } = useDarkMode();
    const containerRef = useRef<HTMLDivElement>(null);
    const [isMounted, setIsMounted] = useState(false);
    const [theme, setTheme] = useState(isDarkMode ? THEMES.dark : THEMES.light);

    useEffect(() => {
        const el = containerRef.current;
        if (!el) return;
        const dark = !isDarkMode && hasDarkParent(el) ? true : isDarkMode;
        setTheme(dark ? THEMES.dark : THEMES.light);
        setIsMounted(true);
    }, [isDarkMode]);

    const customStyle = useMemo(() => ({
        fontSize: 12,
        padding: 0,
        margin: 0,
        lineHeight: 2,
        background: 'none',
        border: 'none',
    }), []);

    if (!isMounted) {
        return <div ref={containerRef} className={className} />;
    }

    return (
        <div ref={containerRef} className={className}>
            <SyntaxHighlighter
                language="sql"
                style={theme}
                customStyle={customStyle}
                wrapLongLines={wrapLongLines}
                codeTagProps={{ style: { background: 'none' } }}
            >
                {children}
            </SyntaxHighlighter>
        </div>
    );
}

export default memo(SqlSyntaxHighlighter);
