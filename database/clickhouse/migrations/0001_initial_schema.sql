-- OpenWatch ClickHouse Schema
-- All telemetry tables use MergeTree with TTL-based retention.
-- Run via: php artisan clickhouse:migrate

CREATE TABLE IF NOT EXISTS extraction_requests
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    trace_id            Nullable(String),
    user                Nullable(String),
    ip                  Nullable(String),
    method              String,
    url                 String,
    route_name          Nullable(String),
    route_path          Nullable(String),
    route_methods       Nullable(String),
    route_action        Nullable(String),
    route_domain        Nullable(String),
    status_code         UInt16,
    duration            UInt32,
    bootstrap           Nullable(UInt32),
    before_middleware   Nullable(UInt32),
    action              Nullable(UInt32),
    render              Nullable(UInt32),
    after_middleware    Nullable(UInt32),
    terminating         Nullable(UInt32),
    sending             Nullable(UInt32),
    request_size        Nullable(UInt32),
    response_size       Nullable(UInt32),
    peak_memory_usage   Nullable(UInt32),
    exceptions          UInt16 DEFAULT 0,
    queries             UInt16 DEFAULT 0,
    logs                UInt16 DEFAULT 0,
    cache_events        UInt16 DEFAULT 0,
    jobs_queued         UInt16 DEFAULT 0,
    notifications       UInt16 DEFAULT 0,
    outgoing_requests   UInt16 DEFAULT 0,
    lazy_loads          UInt16 DEFAULT 0,
    hydrated_models     UInt32 DEFAULT 0,
    files_read          UInt32 DEFAULT 0,
    files_written       UInt32 DEFAULT 0,
    exception_preview   Nullable(String),
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_exceptions
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    execution_source    String DEFAULT '',
    execution_stage     String DEFAULT '',
    execution_preview   Nullable(String),
    group_key           Nullable(String),
    user                Nullable(String),
    class               String,
    file                Nullable(String),
    line                Nullable(UInt32),
    message             String,
    code                Nullable(String),
    trace               String DEFAULT '',
    handled             UInt8 DEFAULT 0,
    php_version         Nullable(String),
    laravel_version     Nullable(String),
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_queries
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    execution_source    String DEFAULT '',
    execution_stage     String DEFAULT '',
    execution_preview   Nullable(String),
    user                Nullable(String),
    sql_hash            String,
    sql_normalized      String,
    file                Nullable(String),
    line                Nullable(UInt32),
    connection          String,
    connection_type     String,
    duration            UInt32,
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_logs
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    execution_source    String DEFAULT '',
    execution_stage     String DEFAULT '',
    execution_preview   Nullable(String),
    user                Nullable(String),
    level               LowCardinality(String),
    message             String,
    context             String DEFAULT '{}',
    extra               String DEFAULT '{}',
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_cache_events
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    execution_source    String DEFAULT '',
    execution_stage     String DEFAULT '',
    execution_preview   Nullable(String),
    user                Nullable(String),
    store               String,
    key                 String,
    type                LowCardinality(String),
    duration            UInt32,
    ttl                 Nullable(UInt32),
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_commands
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    name                String,
    command             Nullable(String),
    class               Nullable(String),
    exit_code           Nullable(Int32),
    duration            Nullable(UInt32),
    bootstrap           Nullable(UInt32),
    action              Nullable(UInt32),
    terminating         Nullable(UInt32),
    peak_memory_usage   Nullable(UInt64),
    exceptions          UInt16 DEFAULT 0,
    queries             UInt16 DEFAULT 0,
    logs                UInt16 DEFAULT 0,
    cache_events        UInt16 DEFAULT 0,
    jobs_queued         UInt16 DEFAULT 0,
    notifications       UInt16 DEFAULT 0,
    outgoing_requests   UInt16 DEFAULT 0,
    lazy_loads          UInt16 DEFAULT 0,
    hydrated_models     UInt32 DEFAULT 0,
    files_read          UInt32 DEFAULT 0,
    files_written       UInt32 DEFAULT 0,
    exception_preview   Nullable(String),
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_notifications
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    execution_source    String DEFAULT '',
    execution_stage     String DEFAULT '',
    execution_preview   Nullable(String),
    user                Nullable(String),
    channel             String,
    class               String,
    duration            Nullable(UInt32),
    failed              UInt8 DEFAULT 0,
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_mails
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    execution_source    String DEFAULT '',
    execution_stage     String DEFAULT '',
    execution_preview   Nullable(String),
    user                Nullable(String),
    mailer              String,
    class               String,
    subject             String,
    `to`                UInt16 DEFAULT 0,
    cc                  UInt16 DEFAULT 0,
    bcc                 UInt16 DEFAULT 0,
    attachments         UInt16 DEFAULT 0,
    duration            Nullable(UInt32),
    failed              UInt8 DEFAULT 0,
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_queued_jobs
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    execution_source    String DEFAULT '',
    execution_stage     String DEFAULT '',
    execution_preview   Nullable(String),
    user                Nullable(String),
    job_id              String,
    name                String,
    connection          String,
    queue               String,
    duration            Nullable(UInt32),
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_job_attempts
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    user                Nullable(String),
    job_id              String,
    attempt_id          String,
    attempt             UInt16,
    name                String,
    connection          String,
    queue               String,
    status              LowCardinality(String),
    duration            Nullable(UInt32),
    peak_memory_usage   Nullable(UInt64),
    exceptions          UInt16 DEFAULT 0,
    queries             UInt16 DEFAULT 0,
    logs                UInt16 DEFAULT 0,
    cache_events        UInt16 DEFAULT 0,
    jobs_queued         UInt16 DEFAULT 0,
    notifications       UInt16 DEFAULT 0,
    outgoing_requests   UInt16 DEFAULT 0,
    lazy_loads          UInt16 DEFAULT 0,
    hydrated_models     UInt32 DEFAULT 0,
    files_read          UInt32 DEFAULT 0,
    files_written       UInt32 DEFAULT 0,
    exception_preview   Nullable(String),
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_scheduled_tasks
(
    id                      String DEFAULT generateUUIDv4(),
    telemetry_record_id     String,
    organization_id         UInt64,
    project_id              UInt64,
    environment_id          UInt64,
    deploy                  String DEFAULT '',
    server                  String DEFAULT '',
    name                    String,
    cron                    String,
    timezone                String DEFAULT 'UTC',
    status                  LowCardinality(String),
    duration                Nullable(UInt32),
    peak_memory_usage       Nullable(UInt64),
    without_overlapping     UInt8 DEFAULT 0,
    on_one_server           UInt8 DEFAULT 0,
    run_in_background       UInt8 DEFAULT 0,
    even_in_maintenance_mode UInt8 DEFAULT 0,
    exceptions              UInt16 DEFAULT 0,
    queries                 UInt16 DEFAULT 0,
    logs                    UInt16 DEFAULT 0,
    cache_events            UInt16 DEFAULT 0,
    jobs_queued             UInt16 DEFAULT 0,
    notifications           UInt16 DEFAULT 0,
    outgoing_requests       UInt16 DEFAULT 0,
    lazy_loads              UInt16 DEFAULT 0,
    hydrated_models         UInt32 DEFAULT 0,
    files_read              UInt32 DEFAULT 0,
    files_written           UInt32 DEFAULT 0,
    exception_preview       Nullable(String),
    recorded_at             DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_outgoing_requests
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    trace_id            Nullable(String),
    execution_id        Nullable(String),
    execution_source    String DEFAULT '',
    execution_stage     String DEFAULT '',
    execution_preview   Nullable(String),
    user                Nullable(String),
    host                String,
    method              String,
    url                 String,
    status_code         Nullable(UInt16),
    duration            UInt32,
    request_size        Nullable(UInt32),
    response_size       Nullable(UInt32),
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;

CREATE TABLE IF NOT EXISTS extraction_user_activities
(
    id                  String DEFAULT generateUUIDv4(),
    telemetry_record_id String,
    organization_id     UInt64,
    project_id          UInt64,
    environment_id      UInt64,
    deploy              String DEFAULT '',
    server              String DEFAULT '',
    user_id             Nullable(String),
    name                Nullable(String),
    username            Nullable(String),
    recorded_at         DateTime64(6, 'UTC')
)
ENGINE = MergeTree()
PARTITION BY toYYYYMM(recorded_at)
ORDER BY (organization_id, project_id, environment_id, recorded_at)
TTL toDateTime(recorded_at) + INTERVAL {telemetry_retention_days:UInt32} DAY DELETE
SETTINGS index_granularity = 8192;
