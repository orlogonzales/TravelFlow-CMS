<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Resolución dinámica de permisos RBAC: USER -> ROLES -> PERMISSIONS -> GATE
        // Ningún rol (incluyendo 'admin') posee facultades implícitas automáticas.
        // Toda autorización deriva exclusivamente de los permisos asignados a sus roles.
        Gate::after(function ($user, string $ability, ?bool $result) {
            if ($result !== null) {
                return $result;
            }

            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability);
            }

            return false;
        });
    }
}
