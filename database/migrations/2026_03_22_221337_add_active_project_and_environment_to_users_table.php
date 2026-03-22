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
            $table->foreignId('active_project_id')->nullable()->constrained('projects')->nullOnDelete()->after('active_organization_id');
            $table->foreignId('active_environment_id')->nullable()->constrained('environments')->nullOnDelete()->after('active_project_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_project_id']);
            $table->dropForeign(['active_environment_id']);
            $table->dropColumn(['active_project_id', 'active_environment_id']);
        });
    }
};
