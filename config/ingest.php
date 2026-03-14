<?php

return [
    'url' => env('INGEST_URL', config('app.url').'/ingest'),
    'session_ttl' => env('INGEST_SESSION_TTL', 3600),
    'refresh_in' => env('INGEST_REFRESH_IN', 300),
];
