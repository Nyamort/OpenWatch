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
        if (Schema::hasColumn('issues', 'user_count')) {
            Schema::table('issues', function (Blueprint $table) {
                $table->dropColumn('user_count');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedBigInteger('user_count')->default(0)->after('occurrence_count');
        });
    }
};
