<?php

namespace App\Services\Auth;

use App\Enums\AuthTokenType;
use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AuthTokenService
{
    /**
     * @return array{
     *     accessToken: string,
     *     refreshToken: string,
     *     tokenType: string,
     *     expiresIn: int
     * }
     */
    public function issueTokenPair(User $user): array
    {
        $accessToken = $this->createToken(
            user: $user,
            type: AuthTokenType::Access,
            expiresInMinutes: (int) config('auth_tokens.access_token_lifetime_minutes'),
        );

        $refreshToken = $this->createToken(
            user: $user,
            type: AuthTokenType::Refresh,
            expiresInMinutes: (int) config('auth_tokens.refresh_token_lifetime_minutes'),
        );

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'tokenType' => 'Bearer',
            'expiresIn' => (int) config('auth_tokens.access_token_lifetime_minutes') * 60,
        ];
    }

    public function findValidToken(string $plainTextToken, AuthTokenType $type): ?AuthToken
    {
        if ($plainTextToken === '') {
            return null;
        }

        return AuthToken::query()
            ->with('user')
            ->where('token_hash', $this->hashToken($plainTextToken))
            ->where('type', $type)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * @return array{
     *     accessToken: string,
     *     refreshToken: string,
     *     tokenType: string,
     *     expiresIn: int
     * }|null
     */
    public function rotateRefreshToken(string $plainTextToken): ?array
    {
        return DB::transaction(function () use ($plainTextToken): ?array {
            $refreshToken = $this->findValidToken($plainTextToken, AuthTokenType::Refresh);

            if ($refreshToken === null || ! $refreshToken->user->is_active) {
                return null;
            }

            $refreshToken->markRevoked();

            return $this->issueTokenPair($refreshToken->user);
        });
    }

    public function revoke(AuthToken $token): void
    {
        $token->markRevoked();
    }

    private function createToken(User $user, AuthTokenType $type, int $expiresInMinutes): string
    {
        $plainTextToken = Str::random(80);

        $user->authTokens()->create([
            'type' => $type,
            'token_hash' => $this->hashToken($plainTextToken),
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);

        return $plainTextToken;
    }

    private function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
