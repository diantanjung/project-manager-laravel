<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withToken;

test('users can be managed through the api', function () {
    $admin = User::factory()->admin()->create();
    $token = domainApiAccessToken($admin);

    $userId = withToken($token)
        ->postJson('/api/v1/users', [
            'name' => 'Dian Project Manager',
            'email' => 'dian.pm@example.com',
            'password' => 'password',
            'role' => UserRole::ProjectManager->value,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Dian Project Manager')
        ->assertJsonPath('data.role', UserRole::ProjectManager->value)
        ->json('data.id');

    $user = User::query()->findOrFail($userId);
    assertModelExists($user);

    withToken($token);

    getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $user->id,
            'email' => 'dian.pm@example.com',
        ]);

    withToken($token);

    getJson("/api/v1/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.email', 'dian.pm@example.com');

    withToken($token)
        ->patchJson("/api/v1/users/{$user->id}", [
            'name' => 'Dian Delivery Lead',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Dian Delivery Lead')
        ->assertJsonPath('data.isActive', false);

    expect($user->refresh()->name)->toBe('Dian Delivery Lead')
        ->and($user->is_active)->toBeFalse();

    withToken($token)
        ->postJson("/api/v1/users/{$user->id}/avatar", [
            'avatar_url' => 'https://example.com/avatar.jpg',
        ])
        ->assertOk()
        ->assertJsonPath('data.avatarUrl', 'https://example.com/avatar.jpg');

    $task = domainApiTask(assignee: $user);

    withToken($token);

    getJson("/api/v1/users/{$user->id}/tasks")
        ->assertOk()
        ->assertJsonPath('data.0.id', $task->id)
        ->assertJsonPath('data.0.assignee.id', $user->id);

    withToken($token)
        ->deleteJson("/api/v1/users/{$user->id}")
        ->assertNoContent();

    assertModelMissing($user);
});

test('user avatar uploads are stored on the configured avatar disk', function () {
    Storage::fake('r2');
    config(['filesystems.avatar_disk' => 'r2']);

    $user = User::factory()->create();
    $token = domainApiAccessToken($user);
    $avatar = UploadedFile::fake()->image('avatar.jpg');
    $expectedPath = 'avatars/'.$avatar->hashName();

    withToken($token)
        ->postJson("/api/v1/users/{$user->id}/avatar", [
            'avatar' => $avatar,
        ])
        ->assertOk()
        ->assertJsonPath('data.avatarUrl', "/storage/{$expectedPath}");

    expect(Storage::disk('r2')->exists($expectedPath))->toBeTrue();
});

test('users can be listed with pagination filters search and sorting', function () {
    $admin = User::factory()->admin()->create();
    $token = domainApiAccessToken($admin);

    User::factory()->projectManager()->create([
        'name' => 'Alpha PM',
        'email' => 'alpha.pm@example.com',
        'created_at' => now()->subDays(3),
    ]);
    User::factory()->projectManager()->create([
        'name' => 'Beta PM',
        'email' => 'beta.pm@example.com',
        'created_at' => now()->subDays(2),
    ]);
    User::factory()->create([
        'name' => 'Alpha Team Member',
        'email' => 'alpha.member@example.com',
        'created_at' => now()->subDay(),
    ]);

    withToken($token);

    getJson('/api/v1/users?search=PM&role=projectManager&sortBy=name&order=desc&limit=1&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha PM')
        ->assertJsonPath('data.0.role', UserRole::ProjectManager->value)
        ->assertJsonPath('pagination.page', 2)
        ->assertJsonPath('pagination.limit', 1)
        ->assertJsonPath('pagination.totalItems', 2)
        ->assertJsonPath('pagination.totalPages', 2);
});

test('users list query parameters are validated', function () {
    $admin = User::factory()->admin()->create();
    $token = domainApiAccessToken($admin);

    withToken($token);

    getJson('/api/v1/users?limit=101&role=owner&sortBy=password&order=sideways&page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['limit', 'role', 'sortBy', 'order', 'page']);
});
