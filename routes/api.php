<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — TravelFlow CMS
|--------------------------------------------------------------------------
|
| Rutas para SPA Administrativa first-party same-origin.
| El middleware 'web' provee sesión encriptada, cookies HttpOnly y protección CSRF.
| 'auth:sanctum' autentica a través de la sesión de Laravel sin Bearer tokens.
|
*/

Route::prefix('auth')->middleware(['web'])->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:sanctum');

    Route::get('/me', [AuthenticatedSessionController::class, 'me'])
        ->middleware(['auth:sanctum', 'active']);
});

// Endpoints técnicos de verificación para RBAC y autorización estricta
Route::middleware(['web', 'auth:sanctum', 'active'])->group(function () {
    Route::get('/test/usuarios', function () {
        return response()->json([
            'success' => true,
            'message' => 'Acceso autorizado al permiso usuarios.ver',
        ]);
    })->middleware('can:usuarios.ver');

    Route::post('/test/usuarios', function () {
        return response()->json([
            'success' => true,
            'message' => 'Acceso autorizado al permiso usuarios.gestionar',
        ]);
    })->middleware('can:usuarios.gestionar');
});
