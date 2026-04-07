-- Drop redundant organization_id, project_id, and telemetry_record_id columns.
-- environment_id alone is sufficient for data isolation.

ALTER TABLE extraction_requests DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_requests DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_requests DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_exceptions DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_exceptions DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_exceptions DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_queries DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_queries DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_queries DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_logs DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_logs DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_logs DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_cache_events DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_cache_events DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_cache_events DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_commands DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_commands DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_commands DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_notifications DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_notifications DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_notifications DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_mails DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_mails DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_mails DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_queued_jobs DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_queued_jobs DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_queued_jobs DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_job_attempts DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_job_attempts DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_job_attempts DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_scheduled_tasks DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_scheduled_tasks DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_scheduled_tasks DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_outgoing_requests DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_outgoing_requests DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_outgoing_requests DROP COLUMN IF EXISTS project_id;

ALTER TABLE extraction_user_activities DROP COLUMN IF EXISTS telemetry_record_id;
ALTER TABLE extraction_user_activities DROP COLUMN IF EXISTS organization_id;
ALTER TABLE extraction_user_activities DROP COLUMN IF EXISTS project_id;
