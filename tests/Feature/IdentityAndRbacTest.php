<?php

namespace Tests\Feature;

use App\Domains\User\Enums\PersonaStatus;
use App\Domains\User\Enums\UserStatus;
use App\Domains\User\Models\Permission;
use App\Domains\User\Models\Persona;
use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdentityAndRbacTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica la creación de Persona y su desacoplamiento de credenciales.
     */
    public function test_persona_creation_and_attributes(): void
    {
        $persona = Persona::create([
            'nombres' => 'Orlando',
            'apellidos' => 'Gonzales',
            'tipo_documento' => 'DNI',
            'numero_documento' => '12345678',
            'email' => 'orlando@example.com',
            'telefono' => '+51987654321',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'numero_documento' => '12345678',
            'estado' => 'activo',
        ]);

        $this->assertSame('Orlando Gonzales', $persona->nombre_completo);
        $this->assertNull($persona->user);
    }

    /**
     * Verifica la relación flexible Persona <-> User.
     */
    public function test_persona_and_user_flexible_relationship(): void
    {
        $persona = Persona::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Mendoza',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $user = User::create([
            'name' => 'Carlos Mendoza',
            'email' => 'carlos@example.com',
            'password' => Hash::make('secret123'),
            'persona_id' => $persona->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertSame($persona->id, $user->persona->id);
        $this->assertSame($user->id, $persona->fresh()->user->id);

        // Usuario puede existir sin Persona obligatoria
        $standaloneUser = User::create([
            'name' => 'Admin Tecnico',
            'email' => 'tech@example.com',
            'password' => Hash::make('secret123'),
            'persona_id' => null,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertNull($standaloneUser->persona);
    }

    /**
     * Verifica que un usuario puede tener múltiples roles y un rol múltiples permisos (N:M).
     */
    public function test_user_can_have_multiple_roles_and_role_multiple_permissions(): void
    {
        $permView = Permission::create([
            'name' => 'Ver Usuarios',
            'slug' => 'usuarios.ver',
            'domain' => 'usuario',
        ]);

        $permManage = Permission::create([
            'name' => 'Gestionar Usuarios',
            'slug' => 'usuarios.gestionar',
            'domain' => 'usuario',
        ]);

        $roleViewer = Role::create([
            'name' => 'Visualizador',
            'slug' => 'viewer',
        ]);

        $roleEditor = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $roleViewer->givePermission($permView);
        $roleEditor->givePermission($permManage);

        $this->assertTrue($roleViewer->hasPermission('usuarios.ver'));
        $this->assertFalse($roleViewer->hasPermission('usuarios.gestionar'));

        $user = User::create([
            'name' => 'Operador Multirol',
            'email' => 'operador@example.com',
            'password' => Hash::make('secret123'),
            'status' => UserStatus::ACTIVE,
        ]);

        // Asignar múltiples roles
        $user->assignRole($roleViewer);
        $user->assignRole($roleEditor);

        $this->assertTrue($user->hasRole('viewer'));
        $this->assertTrue($user->hasRole('editor'));

        // Permisos consolidados
        $this->assertTrue($user->hasPermission('usuarios.ver'));
        $this->assertTrue($user->hasPermission('usuarios.gestionar'));
        $this->assertFalse($user->hasPermission('roles.gestionar'));
    }

    /**
     * Verifica la autorización dinámica mediante Gates y Policies de Laravel.
     */
    public function test_dynamic_gate_authorization_permitted_and_denied(): void
    {
        $permView = Permission::create([
            'name' => 'Ver Usuarios',
            'slug' => 'usuarios.ver',
            'domain' => 'usuario',
        ]);

        $roleViewer = Role::create([
            'name' => 'Lector',
            'slug' => 'lector',
        ]);
        $roleViewer->givePermission($permView);

        $userViewer = User::create([
            'name' => 'Usuario Lector',
            'email' => 'lector@example.com',
            'password' => Hash::make('secret123'),
            'status' => UserStatus::ACTIVE,
        ]);
        $userViewer->assignRole($roleViewer);

        // Autorizado para usuarios.ver
        $this->assertTrue(Gate::forUser($userViewer)->allows('usuarios.ver'));
        // Denegado para usuarios.gestionar
        $this->assertFalse(Gate::forUser($userViewer)->allows('usuarios.gestionar'));
    }

    /**
     * Verifica que el rol 'admin' posee todas las facultades mediante Gate::before.
     */
    public function test_admin_role_has_all_abilities(): void
    {
        $roleAdmin = Role::create([
            'name' => 'Administrador',
            'slug' => 'admin',
            'is_system' => true,
        ]);

        $userAdmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('secret123'),
            'status' => UserStatus::ACTIVE,
        ]);
        $userAdmin->assignRole($roleAdmin);

        // Sin permisos explícitos asignados, Gate::before debe conceder acceso
        $this->assertTrue(Gate::forUser($userAdmin)->allows('usuarios.ver'));
        $this->assertTrue(Gate::forUser($userAdmin)->allows('usuarios.gestionar'));
        $this->assertTrue(Gate::forUser($userAdmin)->allows('cualquier.otra.accion'));
    }

    /**
     * Verifica que las contraseñas se hashean y nunca se almacenan en texto plano.
     */
    public function test_passwords_are_securely_hashed(): void
    {
        $rawPassword = 'MiPasswordSuperSeguro123!';
        $user = User::create([
            'name' => 'Usuario Hash',
            'email' => 'hash@example.com',
            'password' => $rawPassword,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertNotSame($rawPassword, $user->password);
        $this->assertTrue(Hash::check($rawPassword, $user->password));
    }
}
