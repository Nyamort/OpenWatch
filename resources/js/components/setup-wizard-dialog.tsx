import { usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { StepHeader } from './wizard/shared';
import { WizardStep1 } from './wizard/step-1';
import { WizardStep2 } from './wizard/step-2';
import { WizardStep3 } from './wizard/step-3';
import { WizardStep4 } from './wizard/step-4';
import type { CreatedData } from './wizard/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function SetupWizardDialog({ open, onOpenChange }: Props) {
    const { activeOrganization } = usePage().props as {
        activeOrganization?: { id: number; name: string; slug: string } | null;
    };

    const [step, setStep] = useState(1);
    const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set());
    const [created, setCreated] = useState<CreatedData | null>(null);

    function complete(n: number) {
        setCompletedSteps((prev) => new Set([...prev, n]));
    }

    function handleOpenChange(value: boolean) {
        if (!value) {
            setStep(1);
            setCompletedSteps(new Set());
            setCreated(null);
        }
        onOpenChange(value);
    }

    const orgName = activeOrganization?.name ?? '—';
    const projectName = created?.project.name ?? (step === 1 ? 'New Application' : '—');

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-2xl bg-zinc-950 text-zinc-100 border-zinc-800 p-0 gap-0 overflow-hidden">
                <DialogHeader className="border-b border-zinc-800 px-6 py-4">
                    <div className="flex items-center justify-between">
                        <DialogTitle asChild>
                            <div className="flex items-center gap-1.5 text-sm">
                                <span className="text-zinc-500">{orgName}</span>
                                <span className="text-zinc-600">/</span>
                                <span className="font-semibold text-zinc-100">{projectName}</span>
                            </div>
                        </DialogTitle>
                        {completedSteps.has(step - 1) && (
                            <span className="flex items-center gap-1 text-xs font-bold text-emerald-400 mr-8">
                                <CheckCircle2 className="size-3.5" /> DONE
                            </span>
                        )}
                    </div>
                </DialogHeader>

                <div className="flex flex-col divide-y divide-zinc-800 overflow-y-auto max-h-[70vh]">
                    <div className="px-6 py-4">
                        <StepHeader number={1} title="Create Application" done={completedSteps.has(1)} active={step >= 1} />
                        {activeOrganization && (
                            <div className={step !== 1 ? 'hidden' : ''}>
                                <WizardStep1
                                    organizationId={activeOrganization.id}
                                    created={created}
                                    onCreated={(data) => { setCreated(data); complete(1); setStep(2); }}
                                />
                            </div>
                        )}
                    </div>

                    <div className="px-6 py-4">
                        <StepHeader number={2} title="Install Agent" done={completedSteps.has(2)} active={step >= 2} />
                        <div className={step !== 2 ? 'hidden' : ''}>
                            <WizardStep2
                                token={created?.token ?? ''}
                                onNext={() => { complete(2); setStep(3); }}
                                onBack={() => setStep(1)}
                            />
                        </div>
                    </div>

                    <div className="px-6 py-4">
                        <StepHeader number={3} title="Sampling" done={completedSteps.has(3)} active={step >= 3} />
                        <div className={step !== 3 ? 'hidden' : ''}>
                            <WizardStep3
                                onNext={() => { complete(3); setStep(4); }}
                                onBack={() => setStep(2)}
                            />
                        </div>
                    </div>

                    <div className="px-6 py-4">
                        <StepHeader number={4} title="Setup Agent" done={completedSteps.has(4)} active={step >= 4} />
                        {step >= 4 && (
                            <WizardStep4
                                onComplete={() => { complete(4); handleOpenChange(false); }}
                                onSkip={() => handleOpenChange(false)}
                                onBack={() => setStep(3)}
                            />
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
