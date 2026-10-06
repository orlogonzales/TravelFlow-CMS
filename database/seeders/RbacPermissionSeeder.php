<?php

namespace Database\Seeders;

use App\Domains\User\Models\Permission;
use App\Domains\User\Models\Role;
use Illuminate\Database\Seeder;

class RbacPermissionSeeder extends Seeder
{
    /**
     * Sembrado idempotente y reproducible del catálogo oficial de permisos RBAC
     * para FASE 1C.1 (Identidad y Personas).
     */
    public function run(): void
    {
        // 1. Permisos Fundacionales y Fase 1B
        $permAdminAcceder = Permission::firstOrCreate(
            ['slug' => 'admin.acceder'],
            [
                'name' => 'Acceder al Panel Administrativo',
                'domain' => 'admin',
                'description' => 'Permite el acceso estructural al shell administrativo de TravelFlow CMS',
            ]
        );

        $permUsuariosVer = Permission::firstOrCreate(
            ['slug' => 'usuarios.ver'],
            [
                'name' => 'Ver Usuarios',
                'domain' => 'usuario',
                'description' => 'Permite visualizar el listado y fichas de usuarios',
            ]
        );

        // 2. Permisos Oficiales para Fase 1C.1 (Personas)
        $permPersonasVer = Permission::firstOrCreate(
            ['slug' => 'personas.ver'],
            [
                'name' => 'Ver Personas',
                'domain' => 'persona',
                'description' => 'Permite visualizar el listado y detalles de personas',
            ]
        );

        $permPersonasCrear = Permission::firstOrCreate(
            ['slug' => 'personas.crear'],
            [
                'name' => 'Crear Personas',
                'domain' => 'persona',
                'description' => 'Permite registrar nuevas identidades de personas',
            ]
        );

        $permPersonasEditar = Permission::firstOrCreate(
            ['slug' => 'personas.editar'],
            [
                'name' => 'Editar Personas',
                'domain' => 'persona',
                'description' => 'Permite modificar los datos y estado de personas',
            ]
        );

        // 3. Asignación explícita al rol de sistema 'admin'
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrador',
                'description' => 'Rol administrativo de TravelFlow CMS con permisos explícitos',
                'is_system' => true,
            ]
        );

        $adminRole->givePermission($permAdminAcceder);
        $adminRole->givePermission($permUsuariosVer);
        $adminRole->givePermission($permPersonasVer);
        $adminRole->givePermission($permPersonasCrear);
        $adminRole->givePermission($permPersonasEditar);
    }
}
