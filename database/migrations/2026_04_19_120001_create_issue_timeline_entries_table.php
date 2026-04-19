<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_timeline_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('issue_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('eventable_type', 50);
            $table->unsignedBigInteger('eventable_id');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('issue_id')->references('id')->on('issues')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['issue_id', 'occurred_at', 'id'], 'timeline_issue_occurred_idx');
            $table->unique(['eventable_type', 'eventable_id'], 'timeline_eventable_unique');
            $table->index(['actor_id', 'occurred_at'], 'timeline_actor_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_timeline_entries');
    }
};
