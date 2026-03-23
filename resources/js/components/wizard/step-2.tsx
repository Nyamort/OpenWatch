import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { CodeBlock, EnvVar } from './shared';

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
                <p className="text-xs font-semibold text-zinc-400 mb-2">Install the Nightwatch package</p>
                <CodeBlock onCopy="composer require laravel/nightwatch">
                    <span className="text-zinc-100">composer require laravel/nightwatch</span>
                </CodeBlock>
            </div>

            <div>
                <p className="text-xs font-semibold text-zinc-400 mb-2">
                    Add the token to your environment variables
                </p>
                <CodeBlock onCopy={`NIGHTWATCH_TOKEN=${token}`}>
                    <EnvVar name="NIGHTWATCH_TOKEN" value={token || '...'} />
                </CodeBlock>
            </div>

            <div>
                <div className="flex items-center justify-between mb-2">
                    <p className="text-xs font-semibold text-zinc-400">Configure Nightwatch to capture logs</p>
                    <span className="text-xs text-zinc-600">Optional</span>
                </div>
                <div className="flex gap-4 mb-2 border-b border-zinc-800">
                    {(['single', 'stack'] as const).map((tab) => (
                        <button
                            key={tab}
                            onClick={() => setLogTab(tab)}
                            className={cn(
                                'pb-1.5 text-xs font-medium transition-colors border-b-2 -mb-px',
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
                    <CodeBlock onCopy="LOG_CHANNEL=nightwatch">
                        <EnvVar name="LOG_CHANNEL" value="nightwatch" />
                    </CodeBlock>
                ) : (
                    <CodeBlock onCopy="LOG_STACK=nightwatch">
                        <EnvVar name="LOG_STACK" value="nightwatch" />
                    </CodeBlock>
                )}
            </div>

            <div className="flex justify-between">
                <Button variant="ghost" onClick={onBack} className="text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800">
                    Back
                </Button>
                <Button onClick={onNext} className="bg-blue-600 hover:bg-blue-700 text-white">
                    Next
                </Button>
            </div>
        </div>
    );
}
