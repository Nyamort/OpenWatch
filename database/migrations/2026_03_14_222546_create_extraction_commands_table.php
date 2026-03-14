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
        Schema::create('extraction_commands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('telemetry_record_id')->unique();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('environment_id');
            $table->string('name');
            $table->string('class')->nullable();
            $table->tinyInteger('exit_code')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_commands');
    }
};
