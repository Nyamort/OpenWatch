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
        Schema::create('issues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('environment_id');
            $table->string('title');
            $table->string('fingerprint', 64);
            $table->string('type', 30)->default('exception');
            $table->string('status', 20)->default('open');
            $table->string('priority', 20)->default('medium');
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->unsignedBigInteger('occurrence_count')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['organization_id', 'project_id', 'environment_id', 'fingerprint', 'status'], 'issues_fingerprint_open_unique');
            $table->index(['organization_id', 'project_id', 'environment_id', 'status', 'last_seen_at'], 'issues_org_proj_env_status_idx');
            $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
