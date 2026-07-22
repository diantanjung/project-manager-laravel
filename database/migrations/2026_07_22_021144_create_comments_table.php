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

        Schema::create('comments', function (Blueprint $table): void {
            $table->increments('id');
            $table->text('content');
            $table->integer('task_id');
            $table->unsignedBigInteger('author_id');
            $table->timestamps();

            $table->index('task_id');
            $table->index('author_id');
            $table->foreign('task_id', 'comments_task_id_tasks_id_fk')->references('id')->on('tasks');
            $table->foreign('author_id', 'comments_author_id_users_id_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
