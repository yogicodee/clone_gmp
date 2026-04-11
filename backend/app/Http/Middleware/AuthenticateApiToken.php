<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return $this->unauthenticatedResponse();
        }

        $hashedToken = hash('sha256', $plainToken);

        $user = User::query()->where('api_token', $hashedToken)->first();

        if (! $user) {
            return $this->unauthenticatedResponse();
        }

        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }

    private function unauthenticatedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }
}
