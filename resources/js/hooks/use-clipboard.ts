// Credit: https://usehooks-ts.com/
import { useCallback, useState } from 'react';

export type CopiedValue = string | null;
export type CopyFn = (text: string) => Promise<boolean>;
export type UseClipboardReturn = [CopiedValue, CopyFn];

export function useClipboard(successDuration = 2000): UseClipboardReturn {
    const [copiedText, setCopiedText] = useState<CopiedValue>(null);

    const copy: CopyFn = useCallback(
        async (text) => {
            if (!navigator?.clipboard) {
                console.warn('Clipboard not supported');

                return false;
            }

            try {
                await navigator.clipboard.writeText(text);
                setCopiedText(text);

                if (successDuration > 0) {
                    setTimeout(() => setCopiedText(null), successDuration);
                }

                return true;
            } catch (error) {
                console.warn('Copy failed', error);
                setCopiedText(null);

                return false;
            }
        },
        [successDuration],
    );

    return [copiedText, copy];
}
