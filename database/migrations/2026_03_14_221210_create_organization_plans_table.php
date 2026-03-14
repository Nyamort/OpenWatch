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
        Schema::create('organization_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('max_members')->default(5);
            $table->unsignedInteger('max_projects')->default(3);
            $table->unsignedBigInteger('max_ingest_events_per_day')->default(100000);
            $table->unsignedSmallInteger('retention_days')->default(30);
            $table->unsignedTinyInteger('warn_threshold_pct')->default(80);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_plans');
    }
};
