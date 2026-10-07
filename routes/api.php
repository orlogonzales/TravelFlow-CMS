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

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('/preferences', [\App\Http\Controllers\Auth\UserPreferencesController::class, 'show']);
        Route::patch('/preferences', [\App\Http\Controllers\Auth\UserPreferencesController::class, 'update']);
        Route::post('/preferences/reset', [\App\Http\Controllers\Auth\UserPreferencesController::class, 'reset']);
    });
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

    // Administración de Usuarios (Fase 1C.2)
    Route::prefix('admin/usuarios')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UsuarioController::class, 'index'])
            ->middleware('can:usuarios.ver');

        // Rutas especializadas previas a {usuario} para evitar colisiones de Route Model Binding
        Route::get('/personas-elegibles', [\App\Http\Controllers\Admin\UsuarioController::class, 'personasElegibles'])
            ->middleware('can:usuarios.crear');

        Route::get('/roles-disponibles', [\App\Http\Controllers\Admin\UsuarioController::class, 'rolesDisponibles'])
            ->middleware('can:usuarios.roles');

        Route::post('/', [\App\Http\Controllers\Admin\UsuarioController::class, 'store'])
            ->middleware('can:usuarios.crear');

        Route::get('/{usuario}', [\App\Http\Controllers\Admin\UsuarioController::class, 'show'])
            ->middleware('can:usuarios.ver');

        Route::match(['put', 'patch'], '/{usuario}', [\App\Http\Controllers\Admin\UsuarioController::class, 'update'])
            ->middleware('can:usuarios.editar');

        Route::patch('/{usuario}/estado', [\App\Http\Controllers\Admin\UsuarioController::class, 'updateEstado'])
            ->middleware('can:usuarios.estado');

        Route::match(['put', 'patch'], '/{usuario}/roles', [\App\Http\Controllers\Admin\UsuarioController::class, 'updateRoles'])
            ->middleware('can:usuarios.roles');

        Route::match(['put', 'patch'], '/{usuario}/password', [\App\Http\Controllers\Admin\UsuarioController::class, 'updatePassword'])
            ->middleware('can:usuarios.password');
    });

    // Administración de Roles y Permisos (Fase 1C.3)
    Route::prefix('admin/roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\RolController::class, 'index'])
            ->middleware('can:roles.ver');

        Route::post('/', [\App\Http\Controllers\Admin\RolController::class, 'store'])
            ->middleware('can:roles.crear');

        Route::get('/{role}', [\App\Http\Controllers\Admin\RolController::class, 'show'])
            ->middleware('can:roles.ver');

        Route::match(['put', 'patch'], '/{role}', [\App\Http\Controllers\Admin\RolController::class, 'update'])
            ->middleware('can:roles.editar');

        Route::delete('/{role}', [\App\Http\Controllers\Admin\RolController::class, 'destroy'])
            ->middleware('can:roles.eliminar');
    });

    Route::get('/admin/permisos', [\App\Http\Controllers\Admin\PermisoController::class, 'index'])
        ->middleware('can:permisos.ver');
});
