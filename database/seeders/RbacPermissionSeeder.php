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

        // 3. Permisos Oficiales para Fase 1C.2 (Usuarios)
        $permUsuariosCrear = Permission::firstOrCreate(
            ['slug' => 'usuarios.crear'],
            [
                'name' => 'Crear Usuarios',
                'domain' => 'usuario',
                'description' => 'Permite registrar nuevas cuentas de usuario vinculadas a personas',
            ]
        );

        $permUsuariosEditar = Permission::firstOrCreate(
            ['slug' => 'usuarios.editar'],
            [
                'name' => 'Editar Usuarios',
                'domain' => 'usuario',
                'description' => 'Permite modificar la información básica de cuentas de usuario',
            ]
        );

        $permUsuariosRoles = Permission::firstOrCreate(
            ['slug' => 'usuarios.roles'],
            [
                'name' => 'Administrar Roles de Usuario',
                'domain' => 'usuario',
                'description' => 'Permite asignar o modificar los roles asignados a cuentas de usuario',
            ]
        );

        $permUsuariosEstado = Permission::firstOrCreate(
            ['slug' => 'usuarios.estado'],
            [
                'name' => 'Modificar Estado de Usuario',
                'domain' => 'usuario',
                'description' => 'Permite activar, inactivar o bloquear cuentas de usuario',
            ]
        );

        $permUsuariosPassword = Permission::firstOrCreate(
            ['slug' => 'usuarios.password'],
            [
                'name' => 'Restablecer Contraseña de Usuario',
                'domain' => 'usuario',
                'description' => 'Permite restablecer la contraseña de acceso de usuarios',
            ]
        );

        // 4. Asignación explícita al rol de sistema 'admin'
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
        $adminRole->givePermission($permUsuariosCrear);
        $adminRole->givePermission($permUsuariosEditar);
        $adminRole->givePermission($permUsuariosRoles);
        $adminRole->givePermission($permUsuariosEstado);
        $adminRole->givePermission($permUsuariosPassword);
        $adminRole->givePermission($permPersonasVer);
        $adminRole->givePermission($permPersonasCrear);
        $adminRole->givePermission($permPersonasEditar);
    }
}
