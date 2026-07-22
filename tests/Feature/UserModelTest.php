<?php

use App\Enums\UserRole;
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
