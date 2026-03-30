<?php

namespace App\Services\Analytics;

use Carbon\Carbon;
use InvalidArgumentException;

class PeriodService
{
    /**
     * Parse a period string and return a PeriodResult DTO.
     *
     * Supported formats:
     * - Presets: 1h, 24h, 7d, 14d, 30d
     * - Custom: start..end (ISO 8601 dates)
     *
     * @throws InvalidArgumentException
     */
    public function parse(string $period): PeriodResult
    {
        if (str_contains($period, '..')) {
            return $this->parseCustom($period);
        }

        return match ($period) {
            '1h' => new PeriodResult(
                start: now()->subHour(),
                end: now(),
                bucketSeconds: 30,
                label: 'Last 1 hour',
            ),
            '24h' => new PeriodResult(
                start: now()->subHours(24),
                end: now(),
                bucketSeconds: 900,
                label: 'Last 24 hours',
            ),
            '7d' => new PeriodResult(
                start: now()->subDays(7),
                end: now(),
                bucketSeconds: 7200,
                label: 'Last 7 days',
            ),
            '14d' => new PeriodResult(
                start: now()->subDays(14),
                end: now(),
                bucketSeconds: 14400,
                label: 'Last 14 days',
            ),
            '30d' => new PeriodResult(
                start: now()->subDays(30),
                end: now(),
                bucketSeconds: 21600,
                label: 'Last 30 days',
            ),
            default => throw new InvalidArgumentException("Invalid period string: {$period}"),
        };
    }

    /**
     * Parse a custom period in the form start..end.
     *
     * @throws InvalidArgumentException
     */
    private function parseCustom(string $period): PeriodResult
    {
        [$startStr, $endStr] = explode('..', $period, 2);

        try {
            $start = Carbon::parse($startStr);
            $end = Carbon::parse($endStr);
        } catch (\Throwable) {
            throw new InvalidArgumentException("Invalid custom period dates: {$period}");
        }

        if ($start->isAfter($end)) {
            throw new InvalidArgumentException('Custom period start must be before end.');
        }

        $diffDays = $start->diffInDays($end);
        if ($diffDays > 90) {
            throw new InvalidArgumentException('Custom period must not exceed 90 days.');
        }

        $totalSeconds = $start->diffInSeconds($end);

        // Snap to nearest round bucket size that keeps ≤300 data points
        $niceBuckets = [30, 60, 300, 900, 1_800, 3_600, 7_200, 14_400, 21_600, 43_200, 86_400];
        $bucketSeconds = $niceBuckets[array_key_last($niceBuckets)];
        foreach ($niceBuckets as $candidate) {
            if ($totalSeconds / $candidate <= 300) {
                $bucketSeconds = $candidate;
                break;
            }
        }

        $label = $start->toDateString().' – '.$end->toDateString();

        return new PeriodResult(
            start: $start,
            end: $end,
            bucketSeconds: $bucketSeconds,
            label: $label,
        );
    }
}
