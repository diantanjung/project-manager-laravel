<?php

use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('project list is scoped to accessible projects', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $visibleProject = domainApiProject($owner);
    $hiddenProject = domainApiProject($outsider);

    withToken(domainApiAccessToken($owner));

    getJson('/api/v1/projects')
        ->assertOk()
        ->assertJsonFragment(['id' => $visibleProject->id])
        ->assertJsonMissing(['id' => $hiddenProject->id]);
});

test('users cannot access projects outside their project or team scope', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $project = domainApiProject($owner);

    withToken(domainApiAccessToken($outsider));

    getJson("/api/v1/projects/{$project->id}")
        ->assertForbidden();
});

test('team members can access assigned projects', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = domainApiProject($owner);
    $team = Team::query()->create([
        'name' => 'Platform',
        'description' => 'Delivery team',
    ]);

    $team->members()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);
    $project->assignedTeams()->attach($team, [
        'assigned_at' => now(),
    ]);

    withToken(domainApiAccessToken($member));

    getJson("/api/v1/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $project->id);
});

test('users cannot access tasks outside their project or assignment scope', function () {
    $creator = User::factory()->create();
    $outsider = User::factory()->create();
    $task = domainApiTask(creator: $creator);

    withToken(domainApiAccessToken($outsider));

    getJson("/api/v1/tasks/{$task->id}")
        ->assertForbidden();
});

test('team management requires team admin scope', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::query()->create([
        'name' => 'Platform',
        'description' => 'Delivery team',
    ]);

    $team->members()->attach($admin, [
        'role' => 'admin',
        'joined_at' => now(),
    ]);
    $team->members()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    withToken(domainApiAccessToken($member))
        ->patchJson("/api/v1/teams/{$team->id}", [
            'description' => 'Updated by member',
        ])
        ->assertForbidden();

    withToken(domainApiAccessToken($admin))
        ->patchJson("/api/v1/teams/{$team->id}", [
            'description' => 'Updated by admin',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Updated by admin');
});

test('non admins cannot manage other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    withToken(domainApiAccessToken($user));

    getJson('/api/v1/users')
        ->assertForbidden();

    getJson("/api/v1/users/{$otherUser->id}")
        ->assertForbidden();
});
