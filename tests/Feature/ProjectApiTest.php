<?php

use App\Models\Project;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('projects can be managed through the api', function () {
    $owner = User::factory()->create();
    $token = domainApiAccessToken($owner);

    $createdProjectId = withToken($token)
        ->postJson('/api/v1/projects', [
            'name' => 'Mobile app',
            'description' => 'Version two delivery',
            'owner_id' => $owner->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mobile app')
        ->assertJsonPath('data.ownerId', $owner->id)
        ->json('data.id');

    $project = Project::query()->findOrFail($createdProjectId);
    assertModelExists($project);

    withToken($token);

    getJson("/api/v1/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.owner.id', $owner->id);

    withToken($token)
        ->patchJson("/api/v1/projects/{$project->id}", [
            'name' => 'Mobile app launch',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Mobile app launch');

    expect($project->refresh()->name)->toBe('Mobile app launch');

    withToken($token)
        ->deleteJson("/api/v1/projects/{$project->id}")
        ->assertNoContent();

    assertModelMissing($project);
});

test('teams can be assigned to projects through the api', function () {
    $owner = User::factory()->create();
    $token = domainApiAccessToken($owner);
    $project = domainApiProject($owner);
    $team = Team::query()->create([
        'name' => 'Platform',
        'description' => 'Platform team',
    ]);

    withToken($token)
        ->postJson("/api/v1/projects/{$project->id}/teams", [
            'team_id' => $team->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.assignedTeams.0.id', $team->id);

    expect($project->assignedTeams()->whereKey($team->id)->exists())->toBeTrue();

    withToken($token)
        ->postJson("/api/v1/projects/{$project->id}/teams", [
            'team_id' => $team->id,
        ])
        ->assertConflict()
        ->assertJsonPath('message', 'Team is already assigned to this project.');

    withToken($token)
        ->deleteJson("/api/v1/projects/{$project->id}/teams/{$team->id}")
        ->assertNoContent();

    expect($project->assignedTeams()->whereKey($team->id)->exists())->toBeFalse();
});
