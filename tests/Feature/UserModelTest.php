<?php

use App\Enums\UserRole;
use App\Models\AuthToken;
use App\Models\RefreshToken;
use App\Models\User;

test('users default to an active team member role', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::TeamMember)
        ->and($user->is_active)->toBeTrue()
        ->and($user->avatar_url)->toBeNull()
        ->and($user->last_login_at)->toBeNull();
});

test('user factory can create elevated roles', function () {
    expect(User::factory()->admin()->create()->role)->toBe(UserRole::Admin)
        ->and(User::factory()->productOwner()->create()->role)->toBe(UserRole::ProductOwner)
        ->and(User::factory()->projectManager()->create()->role)->toBe(UserRole::ProjectManager);
});

test('user serialization hides credentials and token relationships', function () {
    $user = User::factory()->create([
        'password' => 'password',
        'remember_token' => 'remember-secret',
    ]);

    AuthToken::factory()->for($user)->create([
        'token_hash' => 'access-token-hash',
    ]);
    RefreshToken::query()->create([
        'user_id' => $user->id,
        'hash_token' => 'refresh-token-hash',
        'expires_at' => now()->addDay(),
    ]);

    $serialized = $user->load(['authTokens', 'refreshTokens'])->toArray();

    expect($serialized)->not->toHaveKeys([
        'password',
        'remember_token',
        'auth_tokens',
        'refresh_tokens',
    ]);
});
