<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop all MySQL extraction tables — telemetry data is now stored in ClickHouse.
     */
    public function up(): void
    {
        Schema::dropIfExists('extraction_user_activities');
        Schema::dropIfExists('extraction_scheduled_tasks');
        Schema::dropIfExists('extraction_mails');
        Schema::dropIfExists('extraction_notifications');
        Schema::dropIfExists('extraction_outgoing_requests');
        Schema::dropIfExists('extraction_job_attempts');
        Schema::dropIfExists('extraction_queued_jobs');
        Schema::dropIfExists('extraction_commands');
        Schema::dropIfExists('extraction_cache_events');
        Schema::dropIfExists('extraction_logs');
        Schema::dropIfExists('extraction_queries');
        Schema::dropIfExists('extraction_exceptions');
        Schema::dropIfExists('extraction_requests');
        Schema::dropIfExists('telemetry_records');
    }

    /**
     * Restoration of these tables is handled by running the ClickHouse migration
     * and re-ingesting telemetry data. MySQL extraction tables are not restored here.
     */
    public function down(): void {}
};
