<?php

use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('a user can register and receive token pair', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Dian',
        'email' => 'dian@example.com',
        'password' => 'password',
    ])
        ->assertCreated()
        ->assertJsonPath('user.email', 'dian@example.com')
        ->assertJsonPath('user.role', 'teamMember')
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role'],
            'accessToken',
            'refreshToken',
        ]);

    expect(AuthToken::query()->count())->toBe(2);
});

test('a user can login and fetch their profile with an access token', function () {
    $user = User::factory()->create([
        'email' => 'dian@example.com',
        'password' => 'password',
    ]);

    $accessToken = $this->postJson('/api/v1/auth/login', [
        'email' => 'dian@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->json('accessToken');

    $this->withToken($accessToken)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', 'dian@example.com');
});

test('seeded demo users can login with the demo password', function () {
    $this->seed();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('user.email', 'admin@example.com');
});

test('invalid credentials are rejected', function () {
    User::factory()->create([
        'email' => 'dian@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'dian@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable();
});

test('unsupported password hashes are rejected as invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'dian@example.com',
    ]);

    DB::table('users')
        ->where('id', $user->id)
        ->update(['password' => '$2b$10$mBLP2mvLBsoI52vGDQppbONhKm9bi4Huq4zHc4RJDF/RNbFrr2qtK']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'dian@example.com',
        'password' => 'password',
    ])->assertUnprocessable();
});

test('a refresh token can be rotated', function () {
    User::factory()->create([
        'email' => 'dian@example.com',
        'password' => 'password',
    ]);

    $refreshToken = $this->postJson('/api/v1/auth/login', [
        'email' => 'dian@example.com',
        'password' => 'password',
    ])->json('refreshToken');

    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
    ])
        ->assertOk()
        ->assertJsonStructure([
            'accessToken',
            'refreshToken',
        ]);

    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
    ])->assertUnauthorized();
});

test('logout revokes the current access token', function () {
    User::factory()->create([
        'email' => 'dian@example.com',
        'password' => 'password',
    ]);

    $accessToken = $this->postJson('/api/v1/auth/login', [
        'email' => 'dian@example.com',
        'password' => 'password',
    ])->json('accessToken');

    $this->withToken($accessToken)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('data.status', 'loggedOut');

    $this->withToken($accessToken)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
