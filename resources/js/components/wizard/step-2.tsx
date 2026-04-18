import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { CodeBlock } from '@/components/ui/code-block';
import { cn } from '@/lib/utils';

export function WizardStep2({
    token,
    onNext,
    onBack,
}: {
    token: string;
    onNext: () => void;
    onBack: () => void;
}) {
    const [logTab, setLogTab] = useState<'single' | 'stack'>('single');

    return (
        <div className="mt-4 space-y-5">
            <div>
                <p className="mb-2 text-xs font-semibold text-zinc-400">
                    Install the Nightwatch package
                </p>
                <CodeBlock
                    code="composer require laravel/nightwatch"
                    language="bash"
                    copyable
                    className="rounded-md bg-zinc-900 px-4 py-3"
                />
            </div>

            <div>
                <p className="mb-2 text-xs font-semibold text-zinc-400">
                    Add the token to your environment variables
                </p>
                <CodeBlock
                    code={`NIGHTWATCH_TOKEN=${token || '...'}`}
                    copyValue={`NIGHTWATCH_TOKEN=${token}`}
                    language="bash"
                    copyable
                    className="rounded-md bg-zinc-900 px-4 py-3"
                />
            </div>

            <div>
                <div className="mb-2 flex items-center justify-between">
                    <p className="text-xs font-semibold text-zinc-400">
                        Configure Nightwatch to capture logs
                    </p>
                    <span className="text-xs text-zinc-600">Optional</span>
                </div>
                <div className="mb-2 flex gap-4 border-b border-zinc-800">
                    {(['single', 'stack'] as const).map((tab) => (
                        <button
                            key={tab}
                            onClick={() => setLogTab(tab)}
                            className={cn(
                                '-mb-px border-b-2 pb-1.5 text-xs font-medium transition-colors',
                                logTab === tab
                                    ? 'border-zinc-300 text-zinc-100'
                                    : 'border-transparent text-zinc-500 hover:text-zinc-300',
                            )}
                        >
                            {tab === 'single' ? 'Single Channel' : 'Log Stack'}
                        </button>
                    ))}
                </div>
                {logTab === 'single' ? (
                    <CodeBlock
                        code="LOG_CHANNEL=nightwatch"
                        language="bash"
                        copyable
                        className="rounded-md bg-zinc-900 px-4 py-3"
                    />
                ) : (
                    <CodeBlock
                        code="LOG_STACK=nightwatch"
                        language="bash"
                        copyable
                        className="rounded-md bg-zinc-900 px-4 py-3"
                    />
                )}
            </div>

            <div className="flex justify-between">
                <Button
                    variant="ghost"
                    onClick={onBack}
                    className="text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100"
                >
                    Back
                </Button>
                <Button
                    onClick={onNext}
                    className="bg-blue-600 text-white hover:bg-blue-700"
                >
                    Next
                </Button>
            </div>
        </div>
    );
}
