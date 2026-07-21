<?php

namespace App\Http\Middleware;

use App\Enums\AuthTokenType;
use App\Services\Auth\AuthTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateApiToken
{
    public function __construct(private readonly AuthTokenService $authTokens) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->authTokens->findValidToken(
            plainTextToken: (string) $request->bearerToken(),
            type: AuthTokenType::Access,
        );

        if ($token === null) {
            abort(401, 'Unauthenticated.');
        }

        if (! $token->user->is_active) {
            abort(403, 'This account is inactive.');
        }

        $token->markUsed();
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('authToken', $token);

        return $next($request);
    }
}
