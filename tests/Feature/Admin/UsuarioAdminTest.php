<?php

namespace Tests\Feature\Admin;

use App\Domains\User\Enums\PersonaStatus;
use App\Domains\User\Enums\UserStatus;
use App\Domains\User\Models\Permission;
use App\Domains\User\Models\Persona;
use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use Database\Seeders\RbacPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $unprivilegedUser;
    private Persona $testPersona;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ejecutar seeder RBAC reproducible oficial
        $this->seed(RbacPermissionSeeder::class);

        // 2. Persona vinculada para el usuario admin
        $adminPersona = Persona::create([
            'nombres' => 'Orlando',
            'apellidos' => 'Administrador',
            'tipo_documento' => 'DNI',
            'numero_documento' => '00000001',
            'email' => 'admin@test.local',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $this->adminUser = User::create([
            'persona_id' => $adminPersona->id,
            'name' => 'Orlando Administrador',
            'email' => 'admin@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $this->adminUser->assignRole($adminRole);

        // 3. Usuario sin privilegios
        $unprivilegedPersona = Persona::create([
            'nombres' => 'Sin',
            'apellidos' => 'Privilegios',
            'tipo_documento' => 'DNI',
            'numero_documento' => '00000002',
            'email' => 'sinpermisos@test.local',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $this->unprivilegedUser = User::create([
            'persona_id' => $unprivilegedPersona->id,
            'name' => 'Sin Privilegios',
            'email' => 'sinpermisos@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);

        // 4. Persona elegible para pruebas de creación
        $this->testPersona = Persona::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Elegible',
            'tipo_documento' => 'DNI',
            'numero_documento' => '10203040',
            'email' => 'carlos@test.local',
            'estado' => PersonaStatus::ACTIVO,
        ]);
    }

    /**
     * Un invitado no autenticado recibe 401 en todos los endpoints de Usuarios.
     */
    public function test_guest_is_unauthorized(): void
    {
        $this->getJson('/api/admin/usuarios')->assertStatus(401);
        $this->getJson('/api/admin/usuarios/personas-elegibles')->assertStatus(401);
        $this->getJson('/api/admin/usuarios/roles-disponibles')->assertStatus(401);
        $this->postJson('/api/admin/usuarios', [])->assertStatus(401);
        $this->getJson('/api/admin/usuarios/1')->assertStatus(401);
        $this->putJson('/api/admin/usuarios/1', [])->assertStatus(401);
        $this->patchJson('/api/admin/usuarios/1/estado', [])->assertStatus(401);
        $this->putJson('/api/admin/usuarios/1/roles', [])->assertStatus(401);
        $this->putJson('/api/admin/usuarios/1/password', [])->assertStatus(401);
    }

    /**
     * Un usuario sin los permisos específicos recibe 403 en cada endpoint segregado.
     */
    public function test_user_without_permissions_is_forbidden_on_all_endpoints(): void
    {
        $this->actingAs($this->unprivilegedUser, 'web');

        $this->getJson('/api/admin/usuarios')->assertStatus(403);
        $this->getJson('/api/admin/usuarios/personas-elegibles')->assertStatus(403);
        $this->getJson('/api/admin/usuarios/roles-disponibles')->assertStatus(403);
        $this->postJson('/api/admin/usuarios', [
            'persona_id' => $this->testPersona->id,
            'email' => 'nuevo@test.local',
            'password' => 'PasswordSegura2026!',
            'password_confirmation' => 'PasswordSegura2026!',
        ])->assertStatus(403);

        $this->getJson("/api/admin/usuarios/{$this->adminUser->id}")->assertStatus(403);
        $this->putJson("/api/admin/usuarios/{$this->adminUser->id}", [])->assertStatus(403);
        $this->patchJson("/api/admin/usuarios/{$this->adminUser->id}/estado", [])->assertStatus(403);
        $this->putJson("/api/admin/usuarios/{$this->adminUser->id}/roles", [])->assertStatus(403);
        $this->putJson("/api/admin/usuarios/{$this->adminUser->id}/password", [])->assertStatus(403);
    }

    /**
     * Un usuario con usuarios.ver puede listar y ver la ficha de detalle sin fuga de secretos.
     */
    public function test_user_with_usuarios_ver_can_list_and_view_detail_without_secret_leak(): void
    {
        $response = $this->actingAs($this->adminUser, 'web')
            ->getJson('/api/admin/usuarios');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'status',
                        'persona_id',
                        'persona' => [
                            'id',
                            'nombre_completo',
                            'numero_documento',
                        ],
                        'roles',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        // Verificación estricta: Jamás retornar 'password', 'remember_token' ni hashes
        $jsonContent = $response->getContent();
        $this->assertStringNotContainsString('password', $jsonContent);
        $this->assertStringNotContainsString('remember_token', $jsonContent);
        $this->assertStringNotContainsString('$2y$', $jsonContent);

        // Detalle individual
        $detailResponse = $this->actingAs($this->adminUser, 'web')
            ->getJson("/api/admin/usuarios/{$this->adminUser->id}");

        $detailResponse->assertStatus(200)
            ->assertJsonPath('data.email', 'admin@test.local')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.persona.nombre_completo', 'Orlando Administrador');

        $this->assertStringNotContainsString('remember_token', $detailResponse->getContent());
        $this->assertStringNotContainsString('$2y$', $detailResponse->getContent());
    }

    /**
     * Crear usuario requiere obligatoriamente una Persona física activa (Persona != User).
     */
    public function test_store_user_requires_persona(): void
    {
        $response = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/usuarios', [
                'email' => 'sinpersona@test.local',
                'password' => 'PasswordSegura2026!',
                'password_confirmation' => 'PasswordSegura2026!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['persona_id']);
    }

    /**
     * Crear usuario rechaza personas inactivas o que ya poseen una cuenta vinculada (0..1 <-> 0..1).
     */
    public function test_store_user_rejects_already_linked_or_inactive_persona(): void
    {
        // 1. Persona ya vinculada a un usuario
        $responseLinked = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/usuarios', [
                'persona_id' => $this->adminUser->persona_id,
                'email' => 'repetido@test.local',
                'password' => 'PasswordSegura2026!',
                'password_confirmation' => 'PasswordSegura2026!',
            ]);

        $responseLinked->assertStatus(422)
            ->assertJsonValidationErrors(['persona_id']);

        // 2. Persona inactiva
        $personaInactiva = Persona::create([
            'nombres' => 'Inactiva',
            'apellidos' => 'Persona',
            'estado' => PersonaStatus::INACTIVO,
        ]);

        $responseInactive = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/usuarios', [
                'persona_id' => $personaInactiva->id,
                'email' => 'inactiva@test.local',
                'password' => 'PasswordSegura2026!',
                'password_confirmation' => 'PasswordSegura2026!',
            ]);

        $responseInactive->assertStatus(422)
            ->assertJsonValidationErrors(['persona_id']);
    }

    /**
     * Crear usuario exitoso: normaliza email, valida contraseña mínima de 12 caracteres,
     * almacena hash seguro y asocia roles si tiene usuarios.roles.
     */
    public function test_store_user_successfully_creates_account_and_hashes_password(): void
    {
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $response = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/usuarios', [
                'persona_id' => $this->testPersona->id,
                'email' => '  CARLOS.NUEVO@Test.Local  ',
                'password' => 'Temporal2026Secure!',
                'password_confirmation' => 'Temporal2026Secure!',
                'status' => 'active',
                'roles' => [$adminRole->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'carlos.nuevo@test.local')
            ->assertJsonPath('data.name', 'Carlos Elegible')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.persona_id', $this->testPersona->id);

        $createdUser = User::where('email', 'carlos.nuevo@test.local')->firstOrFail();
        $this->assertTrue(Hash::check('Temporal2026Secure!', $createdUser->password));
        $this->assertTrue($createdUser->hasRole('admin'));
    }

    /**
     * Rechaza contraseñas con menos de 12 caracteres o sin confirmación.
     */
    public function test_store_user_rejects_weak_password(): void
    {
        // Menos de 12 caracteres
        $responseShort = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/usuarios', [
                'persona_id' => $this->testPersona->id,
                'email' => 'corto@test.local',
                'password' => 'Pass1234567', // 11 chars
                'password_confirmation' => 'Pass1234567',
            ]);

        $responseShort->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Sin confirmación coincidente
        $responseMismatch = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/usuarios', [
                'persona_id' => $this->testPersona->id,
                'email' => 'mismatch@test.local',
                'password' => 'PasswordSegura2026!',
                'password_confirmation' => 'PasswordDiferente2026!',
            ]);

        $responseMismatch->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Regla Anti-Escalada en Creación: Un operador sin usuarios.roles no puede asignar roles.
     */
    public function test_store_user_without_usuarios_roles_cannot_assign_roles(): void
    {
        // Crear rol limitado que solo tiene usuarios.crear
        $roleCreator = Role::create([
            'name' => 'Solo Creador',
            'slug' => 'solo_creador',
        ]);
        $permCrear = Permission::where('slug', 'usuarios.crear')->firstOrFail();
        $roleCreator->givePermission($permCrear);

        $creatorUser = User::create([
            'name' => 'Creador Limitado',
            'email' => 'creador@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $creatorUser->assignRole($roleCreator);

        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $response = $this->actingAs($creatorUser, 'web')
            ->postJson('/api/admin/usuarios', [
                'persona_id' => $this->testPersona->id,
                'email' => 'escalado@test.local',
                'password' => 'PasswordSegura2026!',
                'password_confirmation' => 'PasswordSegura2026!',
                'roles' => [$adminRole->id],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['roles']);
    }

    /**
     * Regla Anti-Escalada Capability-Based:
     * Un operador con usuarios.roles NO puede asignar un rol que posea permisos que el operador no posee.
     */
    public function test_anti_escalation_enforces_permissions_subset_rule(): void
    {
        // Operador con usuarios.ver, usuarios.crear, usuarios.roles y personas.ver
        $operadorRole = Role::create([
            'name' => 'Operador Roles',
            'slug' => 'operador_roles',
        ]);
        $operadorRole->givePermission(Permission::where('slug', 'usuarios.ver')->firstOrFail());
        $operadorRole->givePermission(Permission::where('slug', 'usuarios.crear')->firstOrFail());
        $operadorRole->givePermission(Permission::where('slug', 'usuarios.roles')->firstOrFail());
        $operadorRole->givePermission(Permission::where('slug', 'personas.ver')->firstOrFail());

        $operador = User::create([
            'name' => 'Operador A',
            'email' => 'operador@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $operador->assignRole($operadorRole);

        // Rol X: Tiene personas.ver (el operador lo posee -> subset válido)
        $rolX = Role::create([
            'name' => 'Rol Permitido',
            'slug' => 'rol_permitido',
        ]);
        $rolX->givePermission(Permission::where('slug', 'personas.ver')->firstOrFail());

        // Rol Y: Tiene personas.crear (el operador NO lo posee -> intento de escalada)
        $rolY = Role::create([
            'name' => 'Rol Superior',
            'slug' => 'rol_superior',
        ]);
        $rolY->givePermission(Permission::where('slug', 'personas.crear')->firstOrFail());

        $targetUser = User::create([
            'name' => 'Target User',
            'email' => 'target@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);

        // 1. Asignar Rol X debe ser AUTORIZADO (200)
        $this->actingAs($operador, 'web')
            ->putJson("/api/admin/usuarios/{$targetUser->id}/roles", [
                'roles' => [$rolX->id],
            ])
            ->assertStatus(200);

        // 2. Asignar Rol Y debe ser DENEGADO por Anti-Escalada (422)
        $this->actingAs($operador, 'web')
            ->putJson("/api/admin/usuarios/{$targetUser->id}/roles", [
                'roles' => [$rolY->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['roles']);
    }

    /**
     * Actualización base mediante PUT no altera contraseñas, roles ni estado.
     */
    public function test_update_base_information_does_not_modify_password_roles_or_status(): void
    {
        $targetUser = User::create([
            'persona_id' => $this->testPersona->id,
            'name' => 'Carlos Elegible',
            'email' => 'carlos.original@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $nuevaPersona = Persona::create([
            'nombres' => 'Nueva',
            'apellidos' => 'Identidad',
            'tipo_documento' => 'DNI',
            'numero_documento' => '99887766',
            'email' => 'nueva@test.local',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $response = $this->actingAs($this->adminUser, 'web')
            ->putJson("/api/admin/usuarios/{$targetUser->id}", [
                'persona_id' => $nuevaPersona->id,
                'name' => 'Carlos Modificado',
                'email' => 'carlos.modificado@test.local',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'carlos.modificado@test.local')
            ->assertJsonPath('data.name', 'Carlos Modificado')
            ->assertJsonPath('data.persona_id', $nuevaPersona->id);

        $targetUser->refresh();
        $this->assertSame(UserStatus::ACTIVE, $targetUser->status);
        $this->assertTrue(Hash::check('PasswordSegura2026!', $targetUser->password));
    }

    /**
     * Prevención de Self-Lockout: Un usuario no puede auto-inactivarse ni auto-bloquearse.
     */
    public function test_user_cannot_inactivate_or_block_themselves(): void
    {
        $response = $this->actingAs($this->adminUser, 'web')
            ->patchJson("/api/admin/usuarios/{$this->adminUser->id}/estado", [
                'status' => 'inactive',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $responseBlocked = $this->actingAs($this->adminUser, 'web')
            ->patchJson("/api/admin/usuarios/{$this->adminUser->id}/estado", [
                'status' => 'blocked',
            ]);

        $responseBlocked->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Protección de Último Administrador Efectivo:
     * No se puede inactivar, bloquear o despojar de facultades a la única cuenta administradora activa.
     */
    public function test_cannot_inactivate_last_effective_administrator(): void
    {
        // En nuestro setup, $this->adminUser es el único con 'admin.acceder' y 'usuarios.roles' activo.
        // Creamos un segundo usuario administrador y lo autenticamos para intentar inactivar a Orlando
        $admin2Persona = Persona::create([
            'nombres' => 'Admin',
            'apellidos' => 'Secundario',
            'estado' => PersonaStatus::ACTIVO,
        ]);
        $admin2 = User::create([
            'persona_id' => $admin2Persona->id,
            'name' => 'Admin 2',
            'email' => 'admin2@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $admin2->assignRole($adminRole);

        // Ahora hay 2 admins. Admin 2 inactiva a Orlando -> Debe ser permitido (200)
        $this->actingAs($admin2, 'web')
            ->patchJson("/api/admin/usuarios/{$this->adminUser->id}/estado", [
                'status' => 'inactive',
            ])
            ->assertStatus(200);

        // Ahora Orlando está inactivo y Admin 2 es el ÚLTIMO administrador activo.
        // Orlando (ahora inactivo) no puede y si intentamos inactivar a Admin 2 con un tercer usuario autorizado:
        $gestorRole = Role::create([
            'name' => 'Gestor Estados',
            'slug' => 'gestor_estados',
        ]);
        $gestorRole->givePermission(Permission::where('slug', 'usuarios.estado')->firstOrFail());
        $gestor = User::create([
            'name' => 'Gestor Externo',
            'email' => 'gestor@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $gestor->assignRole($gestorRole);

        // Intentar inactivar a Admin 2 (último admin efectivo) debe rebotar con 422
        $this->actingAs($gestor, 'web')
            ->patchJson("/api/admin/usuarios/{$admin2->id}/estado", [
                'status' => 'inactive',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Inactivar o bloquear una cuenta revoca inmediatamente sus sesiones activas en MySQL.
     */
    public function test_inactivating_user_revokes_active_sessions_in_database(): void
    {
        $targetUser = User::create([
            'name' => 'User Con Sesion',
            'email' => 'sesion@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);

        // Simular sesión activa en tabla 'sessions'
        DB::table('sessions')->insert([
            'id' => 'simulated_session_token_123',
            'user_id' => $targetUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'payload' => 'dummy_payload',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['id' => 'simulated_session_token_123']);

        $this->actingAs($this->adminUser, 'web')
            ->patchJson("/api/admin/usuarios/{$targetUser->id}/estado", [
                'status' => 'inactive',
            ])
            ->assertStatus(200);

        // La sesión debe haber sido purgada físicamente de la base de datos
        $this->assertDatabaseMissing('sessions', ['id' => 'simulated_session_token_123']);
    }

    /**
     * Restablecer contraseña requiere mínimo 12 caracteres y revoca sesiones existentes.
     */
    public function test_reset_password_requires_min_12_and_revokes_sessions(): void
    {
        $targetUser = User::create([
            'name' => 'User Reset',
            'email' => 'reset@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);

        DB::table('sessions')->insert([
            'id' => 'session_reset_target_456',
            'user_id' => $targetUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'payload' => 'dummy_payload',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($this->adminUser, 'web')
            ->putJson("/api/admin/usuarios/{$targetUser->id}/password", [
                'password' => 'NuevaPasswordSegura2026!',
                'password_confirmation' => 'NuevaPasswordSegura2026!',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $targetUser->refresh();
        $this->assertTrue(Hash::check('NuevaPasswordSegura2026!', $targetUser->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'session_reset_target_456']);
    }

    /**
     * Consulta asíncrona de personas elegibles retorna solo personas activas sin cuenta de usuario.
     */
    public function test_personas_elegibles_endpoint_returns_only_unlinked_active_personas(): void
    {
        $response = $this->actingAs($this->adminUser, 'web')
            ->getJson('/api/admin/usuarios/personas-elegibles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nombre_completo',
                        'numero_documento',
                    ],
                ],
            ]);

        $ids = collect($response->json('data'))->pluck('id')->all();

        // Debe incluir a $this->testPersona (activa y sin cuenta)
        $this->assertContains($this->testPersona->id, $ids);

        // NO debe incluir la persona del adminUser (ya vinculada)
        $this->assertNotContains($this->adminUser->persona_id, $ids);
    }

    /**
     * Verificación Soberana de RBAC:
     * Un usuario con un rol con slug 'admin' pero SIN el permiso explícito recibe 403 (CERO bypass por slug).
     */
    public function test_admin_role_slug_without_explicit_permission_is_denied(): void
    {
        $fakeAdminRole = Role::create([
            'name' => 'Falso Admin',
            'slug' => 'falso_admin',
            'is_system' => true,
        ]);

        $userFake = User::create([
            'name' => 'Admin Vacio',
            'email' => 'adminvacio@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $userFake->assignRole($fakeAdminRole);

        // Sin permiso usuarios.ver -> 403
        $this->actingAs($userFake, 'web')
            ->getJson('/api/admin/usuarios')
            ->assertStatus(403);
    }

    /**
     * Verificación Soberana Inversa:
     * Un usuario con un rol con nombre no-admin pero con permiso explícito es AUTORIZADO (200).
     */
    public function test_non_admin_role_slug_with_explicit_permission_is_authorized(): void
    {
        $auditorRole = Role::create([
            'name' => 'Auditor Externo',
            'slug' => 'auditor_externo',
            'is_system' => false,
        ]);
        $auditorRole->givePermission(Permission::where('slug', 'usuarios.ver')->firstOrFail());

        $auditor = User::create([
            'name' => 'Auditor',
            'email' => 'auditor@test.local',
            'password' => Hash::make('PasswordSegura2026!'),
            'status' => UserStatus::ACTIVE,
        ]);
        $auditor->assignRole($auditorRole);

        $this->actingAs($auditor, 'web')
            ->getJson('/api/admin/usuarios')
            ->assertStatus(200);
    }

    /**
     * Regla de Integridad Histórica: No existe ruta DELETE para usuarios (Prohibido Hard Delete).
     */
    public function test_no_delete_endpoint_exists_for_usuarios(): void
    {
        $response = $this->actingAs($this->adminUser, 'web')
            ->deleteJson("/api/admin/usuarios/{$this->adminUser->id}");

        // Laravel debe retornar 404 Not Found o 405 Method Not Allowed
        $this->assertContains($response->status(), [404, 405]);
    }
}
