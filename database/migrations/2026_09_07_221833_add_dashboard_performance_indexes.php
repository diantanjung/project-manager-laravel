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
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->index(['entity_type', 'created_at'], 'activity_logs_entity_type_created_at_index');
            $table->index('created_at', 'activity_logs_created_at_index');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->index('created_at', 'tasks_created_at_index');
            $table->index('updated_at', 'tasks_updated_at_index');
            $table->index(['status', 'due_date'], 'tasks_status_due_date_index');
            $table->index(['priority', 'status', 'due_date'], 'tasks_priority_status_due_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_priority_status_due_date_index');
            $table->dropIndex('tasks_status_due_date_index');
            $table->dropIndex('tasks_updated_at_index');
            $table->dropIndex('tasks_created_at_index');
        });

        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropIndex('activity_logs_created_at_index');
            $table->dropIndex('activity_logs_entity_type_created_at_index');
        });
    }
};
