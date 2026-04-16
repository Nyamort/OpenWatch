import type { ExceptionOccurrence } from './types';

export function splitFileLine(fileString: string): [string, string | null] {
    const colonIndex = fileString.lastIndexOf(':');
    if (colonIndex === -1) return [fileString, null];
    const maybeNum = fileString.slice(colonIndex + 1);
    if (!/^\d+$/.test(maybeNum)) return [fileString, null];
    return [fileString.slice(0, colonIndex), maybeNum];
}

export function buildMarkdown(exception: ExceptionOccurrence): string {
    const lines: string[] = [
        `## ${exception.class}`,
        '',
        `> ${exception.message}`,
        '',
        `**File:** \`${exception.file}:${exception.line}\``,
        `**Handled:** ${exception.handled ? 'Yes' : 'No'}`,
        `**PHP:** ${exception.php_version} | **Laravel:** ${exception.laravel_version}`,
        '',
        '### Stack Trace',
        '',
    ];

    exception.trace.forEach((frame, i) => {
        lines.push(`${i + 1}. \`${frame.file}\``);
        if (frame.source) {
            lines.push(`   \`${frame.source}\``);
        }
    });

    return lines.join('\n');
}
