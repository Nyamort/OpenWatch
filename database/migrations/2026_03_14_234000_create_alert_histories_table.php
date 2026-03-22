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
        Schema::create('alert_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('alert_rule_id');
            $table->string('transition', 30);
            $table->decimal('value', 10, 2);
            $table->decimal('threshold', 10, 2);
            $table->timestamp('evaluated_at');
            $table->timestamp('notified_at')->nullable();

            $table->foreign('alert_rule_id')->references('id')->on('alert_rules')->cascadeOnDelete();
            $table->index('alert_rule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_histories');
    }
};
