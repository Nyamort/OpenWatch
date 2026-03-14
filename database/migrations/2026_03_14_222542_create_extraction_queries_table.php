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
        Schema::create('extraction_queries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('telemetry_record_id')->unique();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('environment_id');
            $table->string('trace_id', 36)->nullable();
            $table->string('execution_id', 36)->nullable();
            $table->string('user')->nullable();
            $table->string('sql_hash', 64);
            $table->text('sql_normalized');
            $table->string('connection');
            $table->string('connection_type', 10);
            $table->unsignedInteger('duration');
            $table->timestamp('recorded_at');

            $table->index(['organization_id', 'project_id', 'environment_id', 'recorded_at'], 'qry_org_proj_env_recorded_at');
            $table->index('sql_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_queries');
    }
};
