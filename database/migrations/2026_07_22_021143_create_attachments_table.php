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

        Schema::create('attachments', function (Blueprint $table): void {
            $table->increments('id');
            $table->text('file_name');
            $table->text('file_url');
            $table->integer('file_size')->nullable();
            $table->text('mime_type')->nullable();
            $table->integer('task_id');
            $table->unsignedBigInteger('uploader_id');
            $table->timestamps();

            $table->index('task_id');
            $table->index('uploader_id');
            $table->foreign('task_id', 'attachments_task_id_tasks_id_fk')->references('id')->on('tasks');
            $table->foreign('uploader_id', 'attachments_uploader_id_users_id_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
