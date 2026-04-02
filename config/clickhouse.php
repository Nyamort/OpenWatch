<?php

return [
    'host' => env('CLICKHOUSE_HOST', '127.0.0.1'),
    'http_port' => (int) env('CLICKHOUSE_HTTP_PORT', 8123),
    'database' => env('CLICKHOUSE_DATABASE', 'openwatch'),
    'username' => env('CLICKHOUSE_USERNAME', 'default'),
    'password' => env('CLICKHOUSE_PASSWORD', ''),
    'telemetry_retention_days' => (int) env('CLICKHOUSE_TELEMETRY_RETENTION_DAYS', 30),
];
