ALTER TABLE extraction_requests ADD COLUMN IF NOT EXISTS headers Nullable(String) DEFAULT NULL;
