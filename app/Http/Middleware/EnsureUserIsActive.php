<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        if ($user->isDisabled()) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Tu cuenta está deshabilitada. Contacta al administrador.',
                'code' => 'ACCOUNT_DISABLED',
            ], 403);
        }

        if ($user->isPending()) {
            return response()->json([
                'message' => 'Debes activar tu cuenta con una licencia para realizar esta operación.',
                'code' => 'ACCOUNT_PENDING',
            ], 403);
        }

        if (! $user->isActive()) {
            return response()->json([
                'message' => 'El estado de la cuenta no es válido.',
                'code' => 'INVALID_ACCOUNT_STATUS',
            ], 403);
        }

        return $next($request);
    }
}
