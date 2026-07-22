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

        Schema::create('projects', function (Blueprint $table): void {
            $table->increments('id');
            $table->text('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->timestamps();

            $table->index('owner_id');
            $table->foreign('owner_id', 'projects_owner_id_users_id_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
