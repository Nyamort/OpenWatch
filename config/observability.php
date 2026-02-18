<?php

return [
    'telemetry_retention_days' => (int) env('OBSERVABILITY_TELEMETRY_RETENTION_DAYS', 30),
    'audit_retention_days' => (int) env('OBSERVABILITY_AUDIT_RETENTION_DAYS', 90),
];
