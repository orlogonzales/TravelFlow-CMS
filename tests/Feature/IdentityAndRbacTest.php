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
     * Verifica que un usuario con rol 'admin' pero SIN el permiso X es DENEGADO.
     * Demuestra la eliminación del bypass absoluto: ningún slug concede privilegios automáticamente.
     */
    public function test_admin_user_without_permission_is_denied(): void
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

        // Sin permisos asignados al rol, el administrador NO tiene acceso
        $this->assertFalse($userAdmin->hasPermission('usuarios.eliminar'));
        $this->assertFalse(Gate::forUser($userAdmin)->allows('usuarios.eliminar'));
        $this->assertTrue(Gate::forUser($userAdmin)->denies('usuarios.eliminar'));
    }

    /**
     * Verifica que un usuario con rol 'admin' CON el permiso X asignado a su rol es PERMITIDO.
     * La fuente soberana es siempre la matriz: USER -> ROLES -> PERMISSIONS.
     */
    public function test_admin_user_with_assigned_permission_is_permitted(): void
    {
        $permManage = Permission::create([
            'name' => 'Gestionar Usuarios',
            'slug' => 'usuarios.gestionar',
            'domain' => 'usuario',
        ]);

        $roleAdmin = Role::create([
            'name' => 'Administrador',
            'slug' => 'admin',
            'is_system' => true,
        ]);
        $roleAdmin->givePermission($permManage);

        $userAdmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'admin_con_permiso@example.com',
            'password' => Hash::make('secret123'),
            'status' => UserStatus::ACTIVE,
        ]);
        $userAdmin->assignRole($roleAdmin);

        $this->assertTrue($userAdmin->hasPermission('usuarios.gestionar'));
        $this->assertTrue(Gate::forUser($userAdmin)->allows('usuarios.gestionar'));

        // Permiso no asignado sigue denegado
        $this->assertFalse($userAdmin->hasPermission('configuracion.seguridad'));
        $this->assertFalse(Gate::forUser($userAdmin)->allows('configuracion.seguridad'));
    }

    /**
     * Verifica que cualquier otro rol (no admin) con el permiso asignado es PERMITIDO,
     * confirmando que no existe comportamiento diferenciado o privilegiado por slug.
     */
    public function test_other_role_with_assigned_permission_is_permitted(): void
    {
        $permView = Permission::create([
            'name' => 'Ver Tours',
            'slug' => 'tours.ver',
            'domain' => 'tours',
        ]);

        $roleEditor = Role::create([
            'name' => 'Editor de Tours',
            'slug' => 'editor_tours',
        ]);
        $roleEditor->givePermission($permView);

        $userEditor = User::create([
            'name' => 'Operador Tours',
            'email' => 'tours@example.com',
            'password' => Hash::make('secret123'),
            'status' => UserStatus::ACTIVE,
        ]);
        $userEditor->assignRole($roleEditor);

        $this->assertTrue($userEditor->hasPermission('tours.ver'));
        $this->assertTrue(Gate::forUser($userEditor)->allows('tours.ver'));
        $this->assertFalse(Gate::forUser($userEditor)->allows('tours.eliminar'));
    }

    /**
     * Verifica la cardinalidad aprobada: Persona 0..1 <-> 0..1 User.
     * Una Persona puede existir sin cuenta de usuario.
     */
    public function test_persona_can_exist_without_user(): void
    {
        $persona = Persona::create([
            'nombres' => 'Guía',
            'apellidos' => 'Turístico',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $this->assertNull($persona->user);
    }

    /**
     * Verifica la cardinalidad aprobada: una Persona puede tener a lo sumo un User.
     */
    public function test_persona_can_have_at_most_one_user(): void
    {
        $persona = Persona::create([
            'nombres' => 'Elena',
            'apellidos' => 'Ramos',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $user = User::create([
            'name' => 'Elena Ramos',
            'email' => 'elena@example.com',
            'password' => Hash::make('secret123'),
            'persona_id' => $persona->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertSame($user->id, $persona->fresh()->user->id);
        $this->assertSame($persona->id, $user->persona->id);
    }

    /**
     * Verifica que MySQL rechaza una segunda cuenta User para la misma Persona
     * mediante la restricción física UNIQUE(persona_id) a nivel de motor de base de datos.
     */
    public function test_second_user_for_same_persona_is_rejected_by_mysql_unique_constraint(): void
    {
        $persona = Persona::create([
            'nombres' => 'Mario',
            'apellidos' => 'Vargas',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        User::create([
            'name' => 'Mario Cuenta 1',
            'email' => 'mario1@example.com',
            'password' => Hash::make('secret123'),
            'persona_id' => $persona->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Intento de crear una segunda cuenta para la misma persona física
        User::create([
            'name' => 'Mario Cuenta 2',
            'email' => 'mario2@example.com',
            'password' => Hash::make('secret123'),
            'persona_id' => $persona->id,
            'status' => UserStatus::ACTIVE,
        ]);
    }

    /**
     * Verifica que múltiples cuentas de usuario técnicas/transitorias pueden coexistir con persona_id = NULL
     * en MySQL sin infringir el índice UNIQUE(persona_id).
     */
    public function test_multiple_users_can_have_null_persona(): void
    {
        $user1 = User::create([
            'name' => 'Sistema 1',
            'email' => 'sistema1@example.com',
            'password' => Hash::make('secret123'),
            'persona_id' => null,
            'status' => UserStatus::ACTIVE,
        ]);

        $user2 = User::create([
            'name' => 'Sistema 2',
            'email' => 'sistema2@example.com',
            'password' => Hash::make('secret123'),
            'persona_id' => null,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertNull($user1->persona_id);
        $this->assertNull($user2->persona_id);
        $this->assertDatabaseHas('users', ['email' => 'sistema1@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'sistema2@example.com']);
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
