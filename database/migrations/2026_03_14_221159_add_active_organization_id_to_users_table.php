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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('active_organization_id')->nullable()->after('id');
            $table->string('timezone')->default('UTC')->after('active_organization_id');
            $table->string('locale')->default('en')->after('timezone');
            $table->json('display_preferences')->nullable()->after('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['active_organization_id', 'timezone', 'locale', 'display_preferences']);
        });
    }
};
