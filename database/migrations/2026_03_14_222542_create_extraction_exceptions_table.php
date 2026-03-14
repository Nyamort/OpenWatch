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
        Schema::create('extraction_exceptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('telemetry_record_id')->unique();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('environment_id');
            $table->string('trace_id', 36)->nullable();
            $table->string('execution_id', 36)->nullable();
            $table->string('group_key', 64)->nullable();
            $table->string('user', 255)->nullable();
            $table->string('class');
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->text('message');
            $table->boolean('handled')->default(false);
            $table->string('php_version', 20)->nullable();
            $table->string('laravel_version', 20)->nullable();
            $table->timestamp('recorded_at');

            $table->index(['organization_id', 'project_id', 'environment_id', 'recorded_at'], 'exc_org_proj_env_recorded_at');
            $table->index('group_key');
            $table->index('class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_exceptions');
    }
};
