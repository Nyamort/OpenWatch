<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('environment_id');
            $table->string('name');
            $table->string('metric', 50);
            $table->string('operator', 5);
            $table->decimal('threshold', 10, 2);
            $table->unsignedSmallInteger('window_minutes');
            $table->boolean('enabled')->default(true);
            $table->boolean('create_issue_on_trigger')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'project_id', 'environment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
