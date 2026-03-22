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
        Schema::create('issue_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('issue_id');
            $table->string('source_type', 30);
            $table->string('trace_id', 36)->nullable();
            $table->string('group_key', 64)->nullable();
            $table->string('execution_id', 36)->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamp('created_at');

            $table->foreign('issue_id')->references('id')->on('issues')->cascadeOnDelete();
            $table->index('issue_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_sources');
    }
};
