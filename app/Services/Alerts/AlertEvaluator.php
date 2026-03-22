<?php

namespace App\Services\Alerts;

use App\Models\AlertRule;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class AlertEvaluator
{
    /**
     * Evaluate an alert rule against current telemetry data.
     * Returns ['value' => float, 'triggered' => bool]
     *
     * @return array{value: float, triggered: bool}
     */
    public function evaluate(AlertRule $rule): array
    {
        $windowStart = now()->subMinutes($rule->window_minutes);
        $windowEnd = now();

        $value = match ($rule->metric) {
            'error_rate' => $this->computeErrorRate($rule, $windowStart, $windowEnd),
            'exception_count' => $this->computeExceptionCount($rule, $windowStart, $windowEnd),
            'request_count' => $this->computeRequestCount($rule, $windowStart, $windowEnd),
            'job_failure_rate' => $this->computeJobFailureRate($rule, $windowStart, $windowEnd),
            'p95_duration' => $this->computeP95Duration($rule, $windowStart, $windowEnd),
            default => 0.0,
        };

        $triggered = $this->compare($value, $rule->operator, (float) $rule->threshold);

        return ['value' => $value, 'triggered' => $triggered];
    }

    private function computeErrorRate(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): float
    {
        $result = DB::table('extraction_requests')
            ->where('organization_id', $rule->organization_id)
            ->where('project_id', $rule->project_id)
            ->where('environment_id', $rule->environment_id)
            ->whereBetween('recorded_at', [$start, $end])
            ->selectRaw('COUNT(*) as total, CAST(SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS UNSIGNED) as errors')
            ->first();

        if (! $result || $result->total == 0) {
            return 0.0;
        }

        return round(($result->errors / $result->total) * 100.0, 2);
    }

    private function computeExceptionCount(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): float
    {
        return (float) DB::table('extraction_exceptions')
            ->where('organization_id', $rule->organization_id)
            ->where('project_id', $rule->project_id)
            ->where('environment_id', $rule->environment_id)
            ->whereBetween('recorded_at', [$start, $end])
            ->count();
    }

    private function computeRequestCount(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): float
    {
        return (float) DB::table('extraction_requests')
            ->where('organization_id', $rule->organization_id)
            ->where('project_id', $rule->project_id)
            ->where('environment_id', $rule->environment_id)
            ->whereBetween('recorded_at', [$start, $end])
            ->count();
    }

    private function computeJobFailureRate(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): float
    {
        $result = DB::table('extraction_job_attempts')
            ->where('organization_id', $rule->organization_id)
            ->where('project_id', $rule->project_id)
            ->where('environment_id', $rule->environment_id)
            ->whereBetween('recorded_at', [$start, $end])
            ->selectRaw("COUNT(*) as total, CAST(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS UNSIGNED) as failed")
            ->first();

        if (! $result || $result->total == 0) {
            return 0.0;
        }

        return round(($result->failed / $result->total) * 100.0, 2);
    }

    private function computeP95Duration(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): float
    {
        // MySQL-compatible approximation: use MAX as upper bound
        $result = DB::table('extraction_requests')
            ->where('organization_id', $rule->organization_id)
            ->where('project_id', $rule->project_id)
            ->where('environment_id', $rule->environment_id)
            ->whereBetween('recorded_at', [$start, $end])
            ->selectRaw('MAX(duration) as max_duration')
            ->first();

        return (float) ($result?->max_duration ?? 0);
    }

    private function compare(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            default => false,
        };
    }
}
