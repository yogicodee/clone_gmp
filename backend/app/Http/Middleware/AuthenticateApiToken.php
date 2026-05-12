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

        if ($user->api_token_expires_at === null || $user->api_token_expires_at->isPast()) {
            $user->revokeApiToken();

            return $this->unauthenticatedResponse('Token telah kedaluwarsa. Silakan login kembali.');
        }

        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }

    private function unauthenticatedResponse(string $message = 'Unauthenticated.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}
