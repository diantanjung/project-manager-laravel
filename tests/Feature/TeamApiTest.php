<?php

use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('teams can be managed through the api', function () {
    $token = domainApiAccessToken();

    $teamId = withToken($token)
        ->postJson('/api/v1/teams', [
            'name' => 'Engineering',
            'description' => 'Product engineering',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Engineering')
        ->json('data.id');

    $team = Team::query()->findOrFail($teamId);

    withToken($token);

    getJson('/api/v1/teams')
        ->assertOk()
        ->assertJsonPath('data.0.id', $team->id);

    withToken($token)
        ->patchJson("/api/v1/teams/{$team->id}", [
            'description' => 'Platform engineering',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Platform engineering');

    withToken($token)
        ->deleteJson("/api/v1/teams/{$team->id}")
        ->assertNoContent();

    assertModelMissing($team);
});

test('teams can be listed with pagination filters search and sorting', function () {
    $token = domainApiAccessToken();

    Team::query()->create([
        'name' => 'Alpha Engineering',
        'description' => 'Platform delivery',
        'created_at' => now()->subDays(3),
    ]);
    Team::query()->create([
        'name' => 'Beta Engineering',
        'description' => 'Product delivery',
        'created_at' => now()->subDays(2),
    ]);
    Team::query()->create([
        'name' => 'Gamma Operations',
        'description' => null,
        'created_at' => now()->subDay(),
    ]);

    withToken($token);

    getJson('/api/v1/teams?search=engineering&hasDescription=true&sortBy=name&order=desc&limit=1&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Engineering')
        ->assertJsonPath('data.0.description', 'Platform delivery')
        ->assertJsonPath('pagination.page', 2)
        ->assertJsonPath('pagination.limit', 1)
        ->assertJsonPath('pagination.totalItems', 2)
        ->assertJsonPath('pagination.totalPages', 2);

    withToken($token);

    getJson('/api/v1/teams?hasDescription=false')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Gamma Operations');
});

test('teams list query parameters are validated', function () {
    $token = domainApiAccessToken();

    withToken($token);

    getJson('/api/v1/teams?limit=101&hasDescription=maybe&sortBy=description&order=sideways&page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['limit', 'hasDescription', 'sortBy', 'order', 'page']);
});

test('members can be added to teams through the api', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);
    $member = User::factory()->create();

    withToken($token)
        ->postJson("/api/v1/teams/{$team->id}/members", [
            'user_id' => $member->id,
            'role' => 'admin',
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', $team->id)
        ->assertJsonPath('data.members.0.id', $member->id);

    $teamMember = $team->members()->whereKey($member->id)->firstOrFail();

    expect($teamMember->pivot->role)->toBe('admin')
        ->and($teamMember->pivot->joined_at)->not->toBeNull();
});

test('team members can be listed through the api', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $team->members()->attach($admin, [
        'role' => 'admin',
        'joined_at' => now()->subDay(),
    ]);
    $team->members()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    withToken($token);

    getJson("/api/v1/teams/{$team->id}/members")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $member->id)
        ->assertJsonPath('data.0.membership.role', 'member')
        ->assertJsonPath('data.1.id', $admin->id)
        ->assertJsonPath('data.1.membership.role', 'admin');
});

test('duplicate team membership returns a conflict', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);
    $member = User::factory()->create();

    $team->members()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    withToken($token)
        ->postJson("/api/v1/teams/{$team->id}/members", [
            'user_id' => $member->id,
        ])
        ->assertConflict()
        ->assertJsonPath('message', 'User is already a member of this team.');
});

test('team member payload is validated', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);

    withToken($token)
        ->postJson("/api/v1/teams/{$team->id}/members", [
            'user_id' => 999999,
            'role' => 'maintainer',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id', 'role']);
});

test('members can be removed from teams through the api', function () {
    $token = domainApiAccessToken();
    $team = Team::query()->create([
        'name' => 'Engineering',
        'description' => 'Product engineering',
    ]);
    $member = User::factory()->create();

    $team->members()->attach($member, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    withToken($token)
        ->deleteJson("/api/v1/teams/{$team->id}/members/{$member->id}")
        ->assertNoContent();

    expect($team->members()->whereKey($member->id)->exists())->toBeFalse();
});
