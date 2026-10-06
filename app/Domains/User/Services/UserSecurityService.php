<?php

namespace App\Domains\User\Services;

use App\Domains\User\Enums\UserStatus;
use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Servicio de Seguridad y Reglas de Dominio para Cuentas de Usuario (TF CMS).
 *
 * Centraliza las defensas críticas:
 * - Revocación inmediata de sesiones en base de datos.
 * - Protección del último administrador efectivo (capability-based).
 * - Protección de auto-bloqueo (self-lockout).
 * - Regla anti-escalada de privilegios basada en subconjunto de permisos:
 *   permissions(role) ⊆ permissions(actor).
 */
class UserSecurityService
{
    /**
     * Revoca físicamente todas las sesiones activas de un usuario en la tabla 'sessions'.
     */
    public function revokeActiveSessions(int $userId): int
    {
        if (!Schema::hasTable('sessions')) {
            return 0;
        }

        return DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Determina si la inactivación, bloqueo o remoción de roles del usuario objetivo
     * dejaría al sistema sin ningún administrador efectivo activo.
     *
     * Definición soberana de Administrador Efectivo:
     * - Cuenta en estado 'active'
     * - Posee permiso 'admin.acceder'
     * - Posee permiso 'usuarios.roles'
     */
    public function wouldLeaveZeroEffectiveAdmins(User $targetUser): bool
    {
        // Si el usuario objetivo ni siquiera es administrador efectivo actualmente, alterarlo no reduce el conteo
        if (! $targetUser->isActive() ||
            ! $targetUser->hasPermission('admin.acceder') ||
            ! $targetUser->hasPermission('usuarios.roles')
        ) {
            return false;
        }

        $remainingActiveAdmins = User::query()
            ->where('id', '!=', $targetUser->id)
            ->where('status', UserStatus::ACTIVE)
            ->whereHas('roles.permissions', fn ($q) => $q->where('slug', 'admin.acceder'))
            ->whereHas('roles.permissions', fn ($q) => $q->where('slug', 'usuarios.roles'))
            ->count();

        return $remainingActiveAdmins === 0;
    }

    /**
     * Valida la regla anti-escalada: un operador autenticado solo puede asignar
     * roles cuyos permisos sean un subconjunto estricto de sus propios permisos efectivos.
     *
     * permissions(role) ⊆ permissions(actor)
     *
     * @param  User  $actor  Usuario que ejecuta la asignación
     * @param  array<int>  $roleIds  IDs de los roles que se desean asignar
     */
    public function canAssignRoles(User $actor, array $roleIds): bool
    {
        if (empty($roleIds)) {
            return true;
        }

        // Obtener conjunto de permisos del actor
        $actorPermissionSlugs = $actor->allPermissions()->pluck('slug')->all();
        $actorPermissionMap = array_flip($actorPermissionSlugs);

        // Cargar roles a asignar con sus permisos
        $rolesToAssign = Role::with('permissions')->whereIn('id', $roleIds)->get();

        foreach ($rolesToAssign as $role) {
            foreach ($role->permissions as $permission) {
                if (!isset($actorPermissionMap[$permission->slug])) {
                    // El rol posee un permiso que el actor NO tiene: intento de escalada detectado
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Obtiene los roles que el actor autenticado está facultado para asignar
     * según la regla de subconjunto de permisos.
     *
     * @return Collection<int, Role>
     */
    public function getAssignableRoles(User $actor): Collection
    {
        $actorPermissionSlugs = $actor->allPermissions()->pluck('slug')->all();
        $actorPermissionMap = array_flip($actorPermissionSlugs);

        return Role::with('permissions')->get()->filter(function (Role $role) use ($actorPermissionMap) {
            foreach ($role->permissions as $permission) {
                if (!isset($actorPermissionMap[$permission->slug])) {
                    return false;
                }
            }
            return true;
        })->values();
    }
}
