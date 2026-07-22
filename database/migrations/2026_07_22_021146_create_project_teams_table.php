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

        Schema::create('project_teams', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('project_id');
            $table->integer('team_id');
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['project_id', 'team_id']);
            $table->index('team_id');
            $table->foreign('project_id', 'project_teams_project_id_projects_id_fk')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('team_id', 'project_teams_team_id_teams_id_fk')->references('id')->on('teams')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_teams');
    }
};
