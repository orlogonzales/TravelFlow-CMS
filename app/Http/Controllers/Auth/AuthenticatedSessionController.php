<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador de Autenticación de Sesiones Stateful de TravelFlow CMS.
 *
 * Utiliza sesiones encriptadas y cookies HttpOnly de Laravel / Sanctum sin emitir Bearer tokens.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Inicia sesión, regenera la sesión HTTP y retorna los datos del usuario.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var \App\Domains\User\Models\User $user */
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'message' => 'Autenticación exitosa',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'persona' => $user->persona ? [
                    'id' => $user->persona->id,
                    'nombre_completo' => $user->persona->nombre_completo,
                    'tipo_documento' => $user->persona->tipo_documento,
                    'numero_documento' => $user->persona->numero_documento,
                ] : null,
                'roles' => $user->roles->pluck('slug'),
                'permissions' => $user->allPermissions()->pluck('slug'),
                'ui_preferences' => $user->getEffectiveUiPreferences(),
            ],
        ]);
    }

    /**
     * Cierra la sesión activa, invalida la sesión e invalida el token CSRF.
     */
    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->setUserResolver(fn () => null);
        Auth::forgetGuards();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    /**
     * Retorna los datos del usuario actualmente autenticado en la sesión.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Domains\User\Models\User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'persona' => $user->persona ? [
                    'id' => $user->persona->id,
                    'nombre_completo' => $user->persona->nombre_completo,
                    'tipo_documento' => $user->persona->tipo_documento,
                    'numero_documento' => $user->persona->numero_documento,
                ] : null,
                'roles' => $user->roles->pluck('slug'),
                'permissions' => $user->allPermissions()->pluck('slug'),
                'ui_preferences' => $user->getEffectiveUiPreferences(),
            ],
        ]);
    }
}
