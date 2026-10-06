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

    // Recurso administrativo base protegido: requiere el permiso soberano admin.acceder
    Route::get('/admin/shell-status', function () {
        return response()->json([
            'success' => true,
            'message' => 'Infraestructura administrativa preparada correctamente.',
            'data' => [
                'status' => 'operational',
                'app_name' => config('app.name', 'TravelFlow CMS'),
                'environment' => config('app.env'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ]);
    })->middleware('can:admin.acceder');

    // Administración de Personas (Fase 1C.1)
    Route::prefix('admin/personas')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PersonaController::class, 'index'])
            ->middleware('can:personas.ver');

        Route::post('/', [\App\Http\Controllers\Admin\PersonaController::class, 'store'])
            ->middleware('can:personas.crear');

        Route::get('/{persona}', [\App\Http\Controllers\Admin\PersonaController::class, 'show'])
            ->middleware('can:personas.ver');

        Route::match(['put', 'patch'], '/{persona}', [\App\Http\Controllers\Admin\PersonaController::class, 'update'])
            ->middleware('can:personas.editar');
    });
});
