<?php

use App\Models\User;
use App\Services\Auth\AuthTokenService;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/api/v1/admin-only-test', fn () => response()->json([
        'data' => ['status' => 'ok'],
    ]))->middleware(['api.auth', 'role:admin']);
});

test('role middleware allows matching roles', function () {
    $admin = User::factory()->admin()->create();
    $tokens = app(AuthTokenService::class)->issueTokenPair($admin);

    $this->withToken($tokens['accessToken'])
        ->getJson('/api/v1/admin-only-test')
        ->assertOk()
        ->assertJsonPath('data.status', 'ok');
});

test('role middleware rejects non matching roles', function () {
    $user = User::factory()->create();
    $tokens = app(AuthTokenService::class)->issueTokenPair($user);

    $this->withToken($tokens['accessToken'])
        ->getJson('/api/v1/admin-only-test')
        ->assertForbidden();
});
