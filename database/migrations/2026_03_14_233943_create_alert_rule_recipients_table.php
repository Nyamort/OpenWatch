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
        Schema::create('alert_rule_recipients', function (Blueprint $table) {
            $table->unsignedBigInteger('alert_rule_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['alert_rule_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_rule_recipients');
    }
};
