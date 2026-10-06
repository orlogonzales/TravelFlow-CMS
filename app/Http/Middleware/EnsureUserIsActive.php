<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para asegurar que el usuario autenticado permanezca en estado activo.
 *
 * Si una cuenta es inactivada o bloqueada durante una sesión abierta,
 * se invalida la sesión de inmediato y se retorna HTTP 403 Forbidden.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if (method_exists($user, 'refresh')) {
                $user->refresh();
            }

            if (method_exists($user, 'isActive') && ! $user->isActive()) {
                Auth::guard('web')->logout();

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                $request->setUserResolver(fn () => null);
                Auth::forgetGuards();

                return response()->json([
                    'success' => false,
                    'message' => 'Su cuenta se encuentra inactiva o bloqueada. Comuníquese con el administrador.',
                ], 403);
            }
        }

        return $next($request);
    }
}
