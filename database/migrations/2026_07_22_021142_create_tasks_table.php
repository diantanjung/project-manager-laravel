<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
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

        Schema::create('tasks', function (Blueprint $table): void {
            $table->increments('id');
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('status')->default(TaskStatus::Backlog->value);
            $table->string('priority')->nullable()->default(TaskPriority::Medium->value);
            $table->integer('project_id');
            $table->unsignedBigInteger('creator_id');
            $table->unsignedBigInteger('assignee_id');
            $table->date('due_date')->nullable();
            $table->integer('position')->nullable()->default(0);
            $table->timestamps();

            $table->index('project_id');
            $table->index('creator_id');
            $table->index('assignee_id');
            $table->index('status');
            $table->index('priority');
            $table->index('due_date');
            $table->index(['project_id', 'status', 'position']);
            $table->foreign('project_id', 'tasks_project_id_projects_id_fk')->references('id')->on('projects');
            $table->foreign('creator_id', 'tasks_creator_id_users_id_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assignee_id', 'tasks_assignee_id_users_id_fk')->references('id')->on('users')->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
