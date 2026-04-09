<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('last_ingested_at')->nullable();
            $table->string('health_status')->default('inactive');
            $table->string('color')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('active_project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('active_environment_id')->references('id')->on('environments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_project_id']);
            $table->dropForeign(['active_environment_id']);
        });

        Schema::dropIfExists('environments');
    }
};
