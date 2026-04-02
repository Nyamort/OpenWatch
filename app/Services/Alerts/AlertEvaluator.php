<?php

namespace App\Services\Alerts;

use App\Models\AlertRule;
use App\Services\ClickHouse\ClickHouseService;
use DateTimeInterface;

class AlertEvaluator
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

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
        $where = $this->baseWhere($rule, $start, $end);

        $result = $this->clickhouse->selectOne("
            SELECT count() AS total, countIf(status_code >= 500) AS errors
            FROM extraction_requests {$where}
        ");

        if (! $result || $result->total == 0) {
            return 0.0;
        }

        return round(($result->errors / $result->total) * 100.0, 2);
    }

    private function computeExceptionCount(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): float
    {
        $where = $this->baseWhere($rule, $start, $end);

        return (float) ($this->clickhouse->selectValue("
            SELECT count() FROM extraction_exceptions {$where}
        ") ?? 0);
    }

    private function computeRequestCount(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): float
    {
        $where = $this->baseWhere($rule, $start, $end);

        return (float) ($this->clickhouse->selectValue("
            SELECT count() FROM extraction_requests {$where}
        ") ?? 0);
    }

    private function computeJobFailureRate(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): float
    {
        $where = $this->baseWhere($rule, $start, $end);

        $result = $this->clickhouse->selectOne("
            SELECT count() AS total, countIf(status = 'failed') AS failed
            FROM extraction_job_attempts {$where}
        ");

        if (! $result || $result->total == 0) {
            return 0.0;
        }

        return round(($result->failed / $result->total) * 100.0, 2);
    }

    private function computeP95Duration(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): float
    {
        $where = $this->baseWhere($rule, $start, $end);

        return (float) ($this->clickhouse->selectValue("
            SELECT toFloat64(quantile(0.95)(duration)) FROM extraction_requests {$where}
        ") ?? 0.0);
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

    private function baseWhere(AlertRule $rule, DateTimeInterface $start, DateTimeInterface $end): string
    {
        $orgId = $rule->organization_id;
        $projId = $rule->project_id;
        $envId = $rule->environment_id;
        $startEscaped = ClickHouseService::escape($start);
        $endEscaped = ClickHouseService::escape($end);

        return "WHERE organization_id = {$orgId}
            AND project_id = {$projId}
            AND environment_id = {$envId}
            AND recorded_at BETWEEN {$startEscaped} AND {$endEscaped}";
    }
}
