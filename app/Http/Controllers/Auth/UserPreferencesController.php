<?php

namespace App\Http\Controllers\Auth;

use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateUiPreferencesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Preferencias Visuales del Usuario Autenticado.
 *
 * Cada usuario administra exclusivamente sus propias preferencias de interfaz.
 * La identidad se obtiene soberanamente desde la sesión autenticada ($request->user()).
 */
class UserPreferencesController extends Controller
{
    /**
     * Retorna las preferencias visuales del usuario autenticado.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'preferences' => $user->getEffectiveUiPreferences(),
            'defaults' => User::DEFAULT_UI_PREFERENCES,
        ]);
    }

    /**
     * Actualiza las preferencias visuales del usuario autenticado.
     */
    public function update(UpdateUiPreferencesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();
        $current = is_array($user->ui_preferences) ? $user->ui_preferences : [];

        // Fusión limpia preservando opciones visuales anteriores
        $user->ui_preferences = array_merge($current, $validated);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Preferencias de interfaz guardadas correctamente',
            'preferences' => $user->getEffectiveUiPreferences(),
        ]);
    }

    /**
     * Restablece las preferencias visuales a los valores oficiales predeterminados.
     */
    public function reset(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->ui_preferences = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Preferencias de interfaz restablecidas a los valores predeterminados',
            'preferences' => $user->getEffectiveUiPreferences(),
            'defaults' => User::DEFAULT_UI_PREFERENCES,
        ]);
    }
}
