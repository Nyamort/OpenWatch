<?php

namespace App\Services\Analytics;

use Carbon\Carbon;

/**
 * Builds typed span arrays with pre-computed offsets for timeline rendering.
 */
class SpanBuilder
{
    private int $startUs;

    public function __construct(string $recordedAt, private readonly int $totalDurationUs)
    {
        $this->startUs = (int) Carbon::parse($recordedAt)->getPreciseTimestamp(6);
    }

    public function offset(object $row): int
    {
        $ts = (int) Carbon::parse($row->recorded_at)->getPreciseTimestamp(6);

        return max(0, min($this->totalDurationUs, $ts - $this->startUs));
    }

    /** @return array<string, mixed> */
    public function querySpan(object $q): array
    {
        return [
            'span_type' => 'query',
            'timestamp' => $q->recorded_at,
            'duration' => (int) $q->duration,
            'offset' => $this->offset($q),
            'name' => 'query',
            'description' => $q->sql_normalized,
            'connection' => $q->connection,
            'connection_type' => $q->connection_type ?? null,
            'group' => $q->sql_hash ?? null,
        ];
    }

    /** @return array<string, mixed> */
    public function exceptionSpan(object $e): array
    {
        return [
            'span_type' => 'exception',
            'timestamp' => $e->recorded_at,
            'duration' => 0,
            'offset' => $this->offset($e),
            'name' => 'exception',
            'description' => $e->class,
            'message' => $e->message,
            'handled' => $e->handled ?? null,
        ];
    }

    /** @return array<string, mixed> */
    public function logSpan(object $l): array
    {
        return [
            'span_type' => 'log',
            'timestamp' => $l->recorded_at,
            'duration' => 0,
            'offset' => $this->offset($l),
            'name' => $l->level,
            'description' => $l->message,
        ];
    }

    /** @return array<string, mixed> */
    public function mailSpan(object $m): array
    {
        return [
            'span_type' => 'mail',
            'timestamp' => $m->recorded_at,
            'duration' => (int) $m->duration,
            'offset' => $this->offset($m),
            'name' => 'mail',
            'description' => $m->subject ?: $m->class,
        ];
    }

    /** @return array<string, mixed> */
    public function notificationSpan(object $n): array
    {
        return [
            'span_type' => 'notification',
            'timestamp' => $n->recorded_at,
            'duration' => (int) $n->duration,
            'offset' => $this->offset($n),
            'name' => 'notification',
            'description' => $n->class,
            'channel' => $n->channel,
        ];
    }

    /** @return array<string, mixed> */
    public function cacheSpan(object $c): array
    {
        return [
            'span_type' => 'cache',
            'timestamp' => $c->recorded_at,
            'duration' => (int) $c->duration,
            'offset' => $this->offset($c),
            'name' => $c->type,
            'description' => $c->key,
        ];
    }

    /** @return array<string, mixed> */
    public function outgoingRequestSpan(object $r): array
    {
        return [
            'span_type' => 'outgoing_request',
            'timestamp' => $r->recorded_at,
            'duration' => (int) $r->duration,
            'offset' => $this->offset($r),
            'name' => $r->method,
            'description' => $r->url,
            'status' => $r->status_code,
        ];
    }

    /**
     * Build the execution envelope array.
     *
     * @param  array<int, array<string, mixed>>  $stages
     * @return array<string, mixed>
     */
    public function buildExecution(string $id, string $name, string $description, int $status, string $variant, array $stages): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'duration' => $this->totalDurationUs,
            'offset' => 0,
            'variant' => $variant,
            'stages' => $stages,
        ];
    }

    /**
     * Build a single stage array.
     *
     * @param  array<int, array<string, mixed>>  $spans
     * @return array<string, mixed>
     */
    public static function buildStage(string $id, string $name, string $description, int $duration, int $offset, array $spans): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'duration' => $duration,
            'offset' => $offset,
            'spans' => $spans,
        ];
    }

    /**
     * Build stages from named phase definitions, computing sequential offsets.
     *
     * Each phase: ['id' => string, 'name' => string, 'duration' => int, 'description' => string (optional)]
     * Phases with duration <= 0 are skipped.
     *
     * @param  array<string, list<array<string, mixed>>>  $spansByStage
     * @param  array<int, array<string, mixed>>  $phases
     * @return array<int, array<string, mixed>>
     */
    public static function buildStagesFromPhases(array $spansByStage, array $phases): array
    {
        $cursor = 0;
        $stages = [];

        foreach ($phases as $phase) {
            if ($phase['duration'] <= 0) {
                continue;
            }

            $stages[] = static::buildStage(
                $phase['id'],
                $phase['name'],
                $phase['description'] ?? '',
                $phase['duration'],
                $cursor,
                static::sortByOffset($spansByStage[$phase['id']] ?? []),
            );

            $cursor += $phase['duration'];
        }

        return $stages;
    }

    /**
     * Group spans by execution_stage from multiple event collections.
     *
     * Each pair is [rows, spanMethod] where spanMethod is a callable that maps a row to a span array.
     *
     * @param  array{0: array<int, object>, 1: callable(object): array<string, mixed>}  ...$pairs
     * @return array<string, list<array<string, mixed>>>
     */
    public static function groupByStage(array ...$pairs): array
    {
        $spansByStage = [];

        foreach ($pairs as [$rows, $spanFn]) {
            foreach ($rows as $row) {
                $spansByStage[$row->execution_stage][] = $spanFn($row);
            }
        }

        return $spansByStage;
    }

    /**
     * Sort spans by offset ascending.
     *
     * @param  array<int, array<string, mixed>>  $spans
     * @return array<int, array<string, mixed>>
     */
    public static function sortByOffset(array $spans): array
    {
        usort($spans, fn ($a, $b) => $a['offset'] <=> $b['offset']);

        return $spans;
    }
}
