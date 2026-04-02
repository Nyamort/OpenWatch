-- OpenWatch ClickHouse Schema
-- All telemetry tables use MergeTree with TTL-based retention.
-- Run via: php artisan clickhouse:migrate

CREATE TABLE IF NOT EXISTS telemetry_records
(
    id             String DEFAULT generateUUIDv4(),
    organization_id UInt64,
    project_id     UInt64,
    environment_id UInt64,
    record_type    LowCardinality(String),
    trace_id       Nullable(String),
    group_key      Nullable(String),
    execution_id   Nullable(String),
    payload        String,
    recorded_at    DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_requests
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    trace_id            Nullable(String),
    user                Nullable(String),
    method              String,
    url                 String,
    route_name          Nullable(String),
    route_path          Nullable(String),
    route_methods       Nullable(String),
    route_action        Nullable(String),
    status_code         UInt16,
    duration            UInt32,
    request_size        Nullable(UInt32),
    response_size       Nullable(UInt32),
    peak_memory_usage   Nullable(UInt32),
    exceptions          UInt16 DEFAULT 0,
    queries             UInt16 DEFAULT 0,
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_queries
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    user                Nullable(String),
    sql_hash            String,
    sql_normalized      String,
    connection          String,
    connection_type     String,
    duration            UInt32,
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_cache_events
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    store               String,
    key                 String,
    type                LowCardinality(String),
    duration            UInt32,
    ttl                 Nullable(UInt32),
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_commands
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    name                String,
    class               Nullable(String),
    exit_code           Nullable(Int32),
    duration            Nullable(UInt32),
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_logs
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    level               LowCardinality(String),
    message             String,
    execution_id        Nullable(String),
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_notifications
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    channel             String,
    class               String,
    duration            Nullable(UInt32),
    failed              UInt8 DEFAULT 0,
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_mails
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    mailer              String,
    class               String,
    subject             String,
    `to`                String,
    duration            Nullable(UInt32),
    failed              UInt8 DEFAULT 0,
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_queued_jobs
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    job_id              String,
    name                String,
    connection          String,
    queue               String,
    duration            Nullable(UInt32),
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_job_attempts
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    job_id              String,
    attempt_id          String,
    attempt             UInt16,
    name                String,
    connection          String,
    queue               String,
    status              LowCardinality(String),
    duration            Nullable(UInt32),
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_scheduled_tasks
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    name                String,
    cron                String,
    status              LowCardinality(String),
    duration            Nullable(UInt32),
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_outgoing_requests
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    host                String,
    method              String,
    url                 String,
    status_code         Nullable(UInt16),
    duration            UInt32,
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_exceptions
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    group_key           Nullable(String),
    user                Nullable(String),
    class               String,
    file                Nullable(String),
    line                Nullable(UInt32),
    message             String,
    handled             UInt8 DEFAULT 0,
    php_version         Nullable(String),
    laravel_version     Nullable(String),
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_user_activities
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    recorded_at         DateTime('UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL recorded_at + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;
