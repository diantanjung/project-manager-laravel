<?php

use App\Models\Project;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('projects can be listed with pagination filters search and sorting', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $token = domainApiAccessToken($owner);

    Project::query()->create([
        'name' => 'Alpha Website',
        'description' => 'Frontend delivery',
        'owner_id' => $owner->id,
    ]);
    Project::query()->create([
        'name' => 'Beta Website',
        'description' => 'Backend delivery',
        'owner_id' => $owner->id,
    ]);
    Project::query()->create([
        'name' => 'Gamma Mobile',
        'description' => null,
        'owner_id' => $otherOwner->id,
    ]);

    withToken($token);

    getJson("/api/v1/projects?search=website&ownerId={$owner->id}&hasDescription=true&sortBy=name&order=desc&limit=1&page=2")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Website')
        ->assertJsonPath('data.0.owner.id', $owner->id)
        ->assertJsonPath('pagination.page', 2)
        ->assertJsonPath('pagination.limit', 1)
        ->assertJsonPath('pagination.totalItems', 2)
        ->assertJsonPath('pagination.totalPages', 2);

    withToken(domainApiAccessToken($otherOwner));

    getJson('/api/v1/projects?hasDescription=false')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Gamma Mobile');
});

test('projects list query parameters are validated', function () {
    $token = domainApiAccessToken();

    withToken($token);

    getJson('/api/v1/projects?limit=101&ownerId=999999&hasDescription=maybe&sortBy=owner_id&order=sideways&page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['limit', 'ownerId', 'hasDescription', 'sortBy', 'order', 'page']);
});
