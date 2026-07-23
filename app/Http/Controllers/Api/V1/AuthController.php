<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\AuthToken;
use App\Models\User;
use App\Services\Auth\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthTokenService $authTokens): JsonResponse
    {
        $user = User::query()->create($request->validated());
        $tokens = $authTokens->issueTokenPair($user);
        $userResource = new UserResource($user);

        return response()->json([
            'user' => $userResource,
            'accessToken' => $tokens['accessToken'],
            'refreshToken' => $tokens['refreshToken'],
        ], 201)->cookie($this->refreshTokenCookie($tokens['refreshToken']));
    }

    public function login(LoginRequest $request, AuthTokenService $authTokens): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null || ! $this->passwordMatches($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            abort(403, 'This account is inactive.');
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $tokens = $authTokens->issueTokenPair($user);
        $userResource = new UserResource($user);

        return response()->json([
            'user' => $userResource,
            'accessToken' => $tokens['accessToken'],
            'refreshToken' => $tokens['refreshToken'],
        ])->cookie($this->refreshTokenCookie($tokens['refreshToken']));
    }

    public function refresh(RefreshTokenRequest $request, AuthTokenService $authTokens): JsonResponse
    {
        $validated = $request->validated();
        $tokens = $authTokens->rotateRefreshToken($validated['refresh_token']);

        if ($tokens === null) {
            abort(401, 'The refresh token is invalid or expired.');
        }

        return response()->json([
            'accessToken' => $tokens['accessToken'],
            'refreshToken' => $tokens['refreshToken'],
        ])->cookie($this->refreshTokenCookie($tokens['refreshToken']));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => new UserResource($request->user()),
            ],
        ]);
    }

    public function logout(Request $request, AuthTokenService $authTokens): JsonResponse
    {
        $token = $request->attributes->get('authToken');

        if ($token instanceof AuthToken) {
            $authTokens->revoke($token);
        }

        return response()->json([
            'data' => [
                'status' => 'loggedOut',
            ],
        ])->withoutCookie('refreshToken');
    }

    private function refreshTokenCookie(string $refreshToken): Cookie
    {
        return cookie(
            name: 'refreshToken',
            value: $refreshToken,
            minutes: (int) config('auth_tokens.refresh_token_lifetime_minutes'),
            httpOnly: true,
            sameSite: 'lax',
        );
    }

    private function passwordMatches(string $password, string $hashedPassword): bool
    {
        try {
            return Hash::check($password, $hashedPassword);
        } catch (RuntimeException) {
            return false;
        }
    }
}
