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
        Schema::create('telemetry_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedBigInteger('environment_id')->index();
            $table->string('record_type', 30);
            $table->string('trace_id', 36)->nullable();
            $table->string('group_key', 64)->nullable();
            $table->string('execution_id', 36)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['organization_id', 'project_id', 'environment_id', 'recorded_at'], 'tel_org_proj_env_recorded_at');
            $table->index('record_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_records');
    }
};
