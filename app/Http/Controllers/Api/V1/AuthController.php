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

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthTokenService $authTokens): JsonResponse
    {
        $user = User::query()->create($request->validated());

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'tokens' => $authTokens->issueTokenPair($user),
            ],
        ], 201);
    }

    public function login(LoginRequest $request, AuthTokenService $authTokens): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            abort(403, 'This account is inactive.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'tokens' => $authTokens->issueTokenPair($user),
            ],
        ]);
    }

    public function refresh(RefreshTokenRequest $request, AuthTokenService $authTokens): JsonResponse
    {
        $validated = $request->validated();
        $tokens = $authTokens->rotateRefreshToken($validated['refresh_token']);

        if ($tokens === null) {
            abort(401, 'The refresh token is invalid or expired.');
        }

        return response()->json([
            'data' => [
                'tokens' => $tokens,
            ],
        ]);
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
        ]);
    }
}
