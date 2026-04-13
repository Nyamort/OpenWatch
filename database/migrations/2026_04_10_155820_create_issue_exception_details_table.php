<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('issue_exception_details');
        Schema::create('issue_exception_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_count')->default(0);
            $table->timestamps();
        });

        if (! Schema::hasColumn('issue_sources', 'user_identifier')) {
            Schema::table('issue_sources', function (Blueprint $table) {
                $table->string('user_identifier')->nullable()->after('execution_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_exception_details');

        Schema::table('issue_sources', function (Blueprint $table) {
            $table->dropColumn('user_identifier');
        });
    }
};
