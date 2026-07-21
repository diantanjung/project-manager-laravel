<?php

use App\Models\AuthToken;
use App\Models\User;

test('a user can register and receive token pair', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Dian',
        'email' => 'dian@example.com',
        'password' => 'password',
    ])
        ->assertCreated()
        ->assertJsonPath('data.user.email', 'dian@example.com')
        ->assertJsonPath('data.user.role', 'teamMember')
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'role'],
                'tokens' => ['accessToken', 'refreshToken', 'tokenType', 'expiresIn'],
            ],
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
        ->assertJsonPath('data.user.id', $user->id)
        ->json('data.tokens.accessToken');

    $this->withToken($accessToken)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', 'dian@example.com');
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

test('a refresh token can be rotated', function () {
    User::factory()->create([
        'email' => 'dian@example.com',
        'password' => 'password',
    ]);

    $refreshToken = $this->postJson('/api/v1/auth/login', [
        'email' => 'dian@example.com',
        'password' => 'password',
    ])->json('data.tokens.refreshToken');

    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
    ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'tokens' => ['accessToken', 'refreshToken', 'tokenType', 'expiresIn'],
            ],
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
    ])->json('data.tokens.accessToken');

    $this->withToken($accessToken)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('data.status', 'loggedOut');

    $this->withToken($accessToken)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
