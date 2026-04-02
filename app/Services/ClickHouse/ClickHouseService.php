<?php

namespace App\Services\ClickHouse;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClickHouseService
{
    public function __construct(
        private readonly string $host,
        private readonly int $httpPort,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
    ) {}

    /**
     * Execute a SELECT query and return results as a Collection of stdClass objects.
     */
    public function select(string $sql): Collection
    {
        $response = $this->request($sql.' FORMAT JSONEachRow');

        $rows = collect();
        foreach (explode("\n", trim($response)) as $line) {
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line);
            if ($decoded !== null) {
                $rows->push($decoded);
            }
        }

        return $rows;
    }

    /**
     * Execute a SELECT query and return the first row, or null.
     */
    public function selectOne(string $sql): ?object
    {
        return $this->select($sql)->first();
    }

    /**
     * Execute a SELECT query and return a single scalar value from the first row.
     */
    public function selectValue(string $sql): mixed
    {
        $row = $this->selectOne($sql);
        if ($row === null) {
            return null;
        }
        $values = (array) $row;

        return reset($values) ?: null;
    }

    /**
     * Insert rows into a ClickHouse table using JSONEachRow format.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function insert(string $table, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $jsonRows = implode("\n", array_map('json_encode', $rows));
        $this->request("INSERT INTO {$table} FORMAT JSONEachRow\n{$jsonRows}");
    }

    /**
     * Execute a DDL or administrative SQL statement.
     */
    public function statement(string $sql): void
    {
        $this->request($sql);
    }

    /**
     * Escape a value for safe interpolation into a ClickHouse SQL string.
     */
    public static function escape(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return "'".$value->format('Y-m-d H:i:s')."'";
        }

        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value)."'";
    }

    /**
     * Send a raw SQL string to the ClickHouse HTTP API and return the response body.
     *
     * @throws RuntimeException
     */
    private function request(string $sql): string
    {
        $response = Http::withHeaders([
            'X-ClickHouse-User' => $this->username,
            'X-ClickHouse-Key' => $this->password,
            'X-ClickHouse-Database' => $this->database,
        ])->withBody($sql, 'text/plain')->post("http://{$this->host}:{$this->httpPort}/");

        if ($response->failed()) {
            throw new RuntimeException('ClickHouse query failed: '.$response->body());
        }

        return $response->body();
    }
}
