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
        Schema::create('alert_states', function (Blueprint $table) {
            $table->unsignedBigInteger('alert_rule_id')->primary();
            $table->string('status', 20)->default('ok');
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamp('last_evaluated_at')->nullable();
            $table->decimal('last_value', 10, 2)->nullable();

            $table->foreign('alert_rule_id')->references('id')->on('alert_rules')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_states');
    }
};
