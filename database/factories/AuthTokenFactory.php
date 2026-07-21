<?php

namespace Database\Factories;

use App\Enums\AuthTokenType;
use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuthToken>
 */
class AuthTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => AuthTokenType::Access,
            'token_hash' => hash('sha256', Str::random(80)),
            'last_used_at' => null,
            'expires_at' => now()->addHour(),
            'revoked_at' => null,
        ];
    }
}
