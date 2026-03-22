<?php

namespace App\Jobs;

use App\Models\AlertHistory;
use App\Models\AlertRule;
use App\Models\AlertState;
use App\Services\Alerts\AlertEvaluator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateAlertRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AlertEvaluator $evaluator): void
    {
        AlertRule::query()
            ->where('enabled', true)
            ->with('recipients')
            ->chunk(50, function ($rules) use ($evaluator): void {
                foreach ($rules as $rule) {
                    $this->processRule($rule, $evaluator);
                }
            });
    }

    private function processRule(AlertRule $rule, AlertEvaluator $evaluator): void
    {
        $result = $evaluator->evaluate($rule);
        $value = $result['value'];
        $triggered = $result['triggered'];

        $state = AlertState::firstOrCreate(
            ['alert_rule_id' => $rule->id],
            ['status' => 'ok']
        );

        $previousStatus = $state->status;
        $transition = 'no_change';

        if ($previousStatus === 'ok' && $triggered) {
            $state->status = 'triggered';
            $state->triggered_at = now();
            $state->recovered_at = null;
            $state->last_evaluated_at = now();
            $state->last_value = $value;
            $state->save();
            $transition = 'ok_to_triggered';
            SendAlertTriggeredNotification::dispatch($rule, $value);
        } elseif ($previousStatus === 'triggered' && ! $triggered) {
            $state->status = 'ok';
            $state->recovered_at = now();
            $state->last_evaluated_at = now();
            $state->last_value = $value;
            $state->save();
            $transition = 'triggered_to_ok';
            SendAlertRecoveredNotification::dispatch($rule, $value);
        } else {
            $state->last_evaluated_at = now();
            $state->last_value = $value;
            $state->save();
        }

        AlertHistory::create([
            'alert_rule_id' => $rule->id,
            'transition' => $transition,
            'value' => $value,
            'threshold' => $rule->threshold,
            'evaluated_at' => now(),
        ]);
    }
}
