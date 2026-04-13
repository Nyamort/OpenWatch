<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedBigInteger('user_count')->default(0)->after('occurrence_count');
        });

        Schema::table('issue_sources', function (Blueprint $table) {
            $table->string('user_identifier')->nullable()->after('execution_id');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('user_count');
        });

        Schema::table('issue_sources', function (Blueprint $table) {
            $table->dropColumn('user_identifier');
        });
    }
};
