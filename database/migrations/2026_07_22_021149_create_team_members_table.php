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

        Schema::create('team_members', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('team_id');
            $table->unsignedBigInteger('user_id');
            $table->text('role')->nullable()->default('member');
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['team_id', 'user_id']);
            $table->index('user_id');
            $table->index('role');
            $table->foreign('team_id', 'team_members_team_id_teams_id_fk')->references('id')->on('teams');
            $table->foreign('user_id', 'team_members_user_id_users_id_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
