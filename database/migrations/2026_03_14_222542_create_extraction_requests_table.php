<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('extraction_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('telemetry_record_id')->unique();
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedBigInteger('environment_id')->index();
            $table->string('trace_id', 36)->nullable();
            $table->string('user', 255)->nullable();
            $table->string('method', 10);
            $table->text('url');
            $table->string('route_name')->nullable();
            $table->string('route_path')->nullable();
            $table->string('route_action')->nullable();
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration');
            $table->unsignedInteger('request_size')->nullable();
            $table->unsignedInteger('response_size')->nullable();
            $table->unsignedInteger('peak_memory_usage')->nullable();
            $table->unsignedSmallInteger('exceptions')->default(0);
            $table->unsignedSmallInteger('queries')->default(0);
            $table->timestamp('recorded_at');

            $table->index(['organization_id', 'project_id', 'environment_id', 'recorded_at'], 'req_org_proj_env_recorded_at');
            $table->index('route_name');
            $table->index('status_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_requests');
    }
};
