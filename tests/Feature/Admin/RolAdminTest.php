<?php

namespace Tests\Feature\Admin;

use App\Domains\User\Enums\UserStatus;
use App\Domains\User\Models\Permission;
use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use Database\Seeders\RbacPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacPermissionSeeder::class);

        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $this->adminUser = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@travelflow.pe',
            'password' => Hash::make('password12345'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->adminUser->assignRole($adminRole);
    }

    private function createOperatorWithPermissions(array $permissions): User
    {
        static $counter = 1;

        $role = Role::create([
            'name' => 'Operator Role ' . $counter,
            'slug' => 'operator-role-' . $counter,
            'is_system' => false,
        ]);

        foreach ($permissions as $slug) {
            $perm = Permission::where('slug', $slug)->first();
            if ($perm) {
                $role->givePermission($perm);
            }
        }

        $user = User::create([
            'name' => 'Operator ' . $counter,
            'email' => 'operator' . ($counter++) . '@travelflow.pe',
            'password' => Hash::make('password12345'),
            'status' => UserStatus::ACTIVE,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->getJson('/api/admin/roles')->assertStatus(401);
        $this->postJson('/api/admin/roles', [])->assertStatus(401);
        $this->getJson('/api/admin/permisos')->assertStatus(401);
    }

    public function test_user_without_roles_ver_is_forbidden(): void
    {
        $user = $this->createOperatorWithPermissions(['admin.acceder']);

        $this->actingAs($user, 'web')
            ->getJson('/api/admin/roles')
            ->assertStatus(403);
    }

    public function test_user_with_roles_ver_can_list_roles_with_metadata(): void
    {
        $user = $this->createOperatorWithPermissions(['admin.acceder', 'roles.ver']);

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/admin/roles')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'is_system',
                        'users_count',
                        'permissions_count',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'total',
                ],
            ]);

        $this->assertTrue($response->json('meta.total') >= 1);
    }

    public function test_search_filter_returns_matching_roles(): void
    {
        $user = $this->createOperatorWithPermissions(['admin.acceder', 'roles.ver']);

        Role::create([
            'name' => 'Guía de Montaña',
            'slug' => 'guia-montana',
            'description' => 'Especialista en trekking',
            'is_system' => false,
        ]);

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/admin/roles?search=Guía')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Guía de Montaña', $response->json('data.0.name'));
    }

    public function test_store_role_success_with_permissions(): void
    {
        $perm1 = Permission::where('slug', 'personas.ver')->firstOrFail();
        $perm2 = Permission::where('slug', 'personas.crear')->firstOrFail();

        $payload = [
            'name' => 'Coordinador de Personas',
            'slug' => 'coordinador-personas',
            'description' => 'Gestiona el registro de identidades',
            'permissions' => [$perm1->id, $perm2->id],
        ];

        $response = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/roles', $payload)
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Coordinador de Personas',
                    'slug' => 'coordinador-personas',
                    'is_system' => false,
                ],
            ]);

        $roleId = $response->json('data.id');
        $this->assertDatabaseHas('roles', [
            'id' => $roleId,
            'name' => 'Coordinador de Personas',
            'is_system' => false,
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $roleId,
            'permission_id' => $perm1->id,
        ]);
    }

    public function test_store_role_anti_escalation_blocks_unpossessed_permissions(): void
    {
        // Operador que solo tiene personas.ver y roles.crear (NO tiene personas.editar)
        $user = $this->createOperatorWithPermissions(['admin.acceder', 'roles.crear', 'personas.ver']);

        $permEditar = Permission::where('slug', 'personas.editar')->firstOrFail();

        $payload = [
            'name' => 'Rol Escalado',
            'slug' => 'rol-escalado',
            'permissions' => [$permEditar->id], // Permiso que el operador NO tiene
        ];

        $this->actingAs($user, 'web')
            ->postJson('/api/admin/roles', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['permissions']);
    }

    public function test_update_role_modifies_attributes_and_permissions(): void
    {
        $role = Role::create([
            'name' => 'Rol Editable',
            'slug' => 'rol-editable',
            'description' => 'Original',
            'is_system' => false,
        ]);

        $perm = Permission::where('slug', 'personas.ver')->firstOrFail();

        $payload = [
            'name' => 'Rol Editable Renombrado',
            'description' => 'Actualizado',
            'permissions' => [$perm->id],
        ];

        $this->actingAs($this->adminUser, 'web')
            ->patchJson("/api/admin/roles/{$role->id}", $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Rol Editable Renombrado',
                    'description' => 'Actualizado',
                ],
            ]);

        $role->refresh();
        $this->assertEquals('Rol Editable Renombrado', $role->name);
        $this->assertTrue($role->hasPermission('personas.ver'));
    }

    public function test_system_role_slug_cannot_be_changed(): void
    {
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $payload = [
            'name' => 'Administrador Supremo',
            'slug' => 'nuevo-slug-admin', // Modificación no permitida
        ];

        $this->actingAs($this->adminUser, 'web')
            ->patchJson("/api/admin/roles/{$adminRole->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_cannot_remove_critical_permissions_from_admin_role(): void
    {
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        // Enviar solo 1 permiso prescindible, omitiendo 'admin.acceder', 'usuarios.roles', etc.
        $permPrescindible = Permission::where('slug', 'personas.ver')->firstOrFail();

        $payload = [
            'name' => 'Administrador',
            'permissions' => [$permPrescindible->id],
        ];

        $this->actingAs($this->adminUser, 'web')
            ->patchJson("/api/admin/roles/{$adminRole->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['permissions']);
    }

    public function test_cannot_delete_system_role(): void
    {
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $this->actingAs($this->adminUser, 'web')
            ->deleteJson("/api/admin/roles/{$adminRole->id}")
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('roles', ['slug' => 'admin']);
    }

    public function test_cannot_delete_role_with_assigned_users(): void
    {
        $customRole = Role::create([
            'name' => 'Rol con Usuarios',
            'slug' => 'rol-con-usuarios',
            'is_system' => false,
        ]);

        $user = User::create([
            'name' => 'Usuario Asignado',
            'email' => 'asignado@travelflow.pe',
            'password' => Hash::make('password12345'),
            'status' => UserStatus::ACTIVE,
        ]);
        $user->assignRole($customRole);

        $this->actingAs($this->adminUser, 'web')
            ->deleteJson("/api/admin/roles/{$customRole->id}")
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('roles', ['id' => $customRole->id]);
    }

    public function test_can_delete_custom_role_without_users(): void
    {
        $customRole = Role::create([
            'name' => 'Rol Descartable',
            'slug' => 'rol-descartable',
            'is_system' => false,
        ]);

        $this->actingAs($this->adminUser, 'web')
            ->deleteJson("/api/admin/roles/{$customRole->id}")
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_permisos_index_returns_grouped_catalogue(): void
    {
        $user = $this->createOperatorWithPermissions(['admin.acceder', 'permisos.ver']);

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/admin/permisos')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'domain'],
                ],
                'grouped',
            ]);

        $this->assertArrayHasKey('persona', $response->json('grouped'));
        $this->assertArrayHasKey('usuario', $response->json('grouped'));
        $this->assertArrayHasKey('rol', $response->json('grouped'));
    }
}
