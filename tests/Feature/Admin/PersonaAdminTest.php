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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PersonaAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $unprivilegedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar seeder RBAC reproducible para tener el catálogo y rol admin listos
        $this->seed(RbacPermissionSeeder::class);

        // Crear usuario con rol admin (que posee los permisos explícitos)
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $this->adminUser = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.local',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);
        $this->adminUser->assignRole($adminRole);

        // Crear usuario sin roles ni permisos
        $this->unprivilegedUser = User::create([
            'name' => 'Sin Privilegios',
            'email' => 'sinpermiso@test.local',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);
    }

    /**
     * Un invitado no autenticado recibe 401 en todos los endpoints de Personas.
     */
    public function test_guest_is_unauthorized(): void
    {
        $this->getJson('/api/admin/personas')->assertStatus(401);
        $this->postJson('/api/admin/personas', [])->assertStatus(401);
        $this->getJson('/api/admin/personas/1')->assertStatus(401);
        $this->putJson('/api/admin/personas/1', [])->assertStatus(401);
    }

    /**
     * Un usuario autenticado sin el permiso personas.ver recibe 403 en index y show.
     */
    public function test_user_without_personas_ver_is_forbidden_on_list_and_detail(): void
    {
        $persona = Persona::create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $this->actingAs($this->unprivilegedUser, 'web')
            ->getJson('/api/admin/personas')
            ->assertStatus(403);

        $this->actingAs($this->unprivilegedUser, 'web')
            ->getJson("/api/admin/personas/{$persona->id}")
            ->assertStatus(403);
    }

    /**
     * Un usuario con personas.ver puede listar y consultar detalles.
     */
    public function test_user_with_personas_ver_can_list_and_view_detail(): void
    {
        $persona = Persona::create([
            'nombres' => 'María',
            'apellidos' => 'Quispe',
            'tipo_documento' => 'DNI',
            'numero_documento' => '44556677',
            'email' => 'maria@example.com',
            'telefono' => '987654321',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $response = $this->actingAs($this->adminUser, 'web')
            ->getJson('/api/admin/personas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nombres',
                        'apellidos',
                        'nombre_completo',
                        'tipo_documento',
                        'numero_documento',
                        'email',
                        'telefono',
                        'estado',
                        'has_user',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        $detailResponse = $this->actingAs($this->adminUser, 'web')
            ->getJson("/api/admin/personas/{$persona->id}");

        $detailResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $persona->id,
                    'nombres' => 'María',
                    'apellidos' => 'Quispe',
                    'nombre_completo' => 'María Quispe',
                    'numero_documento' => '44556677',
                    'has_user' => false,
                ],
            ]);
    }

    /**
     * Un usuario sin permiso personas.crear recibe 403 al intentar crear una Persona.
     */
    public function test_user_without_personas_crear_is_forbidden(): void
    {
        $payload = [
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
        ];

        $this->actingAs($this->unprivilegedUser, 'web')
            ->postJson('/api/admin/personas', $payload)
            ->assertStatus(403);
    }

    /**
     * Un usuario con permiso personas.crear crea exitosamente la Persona (201).
     * REGLA VINCULANTE: Crear Persona NO crea una cuenta User.
     */
    public function test_user_with_personas_crear_can_create_persona_and_does_not_create_user(): void
    {
        $payload = [
            'nombres' => '  Pedro  ',
            'apellidos' => '  Castillo  ',
            'tipo_documento' => 'DNI',
            'numero_documento' => '88776655',
            'email' => 'pedro@example.com',
            'telefono' => '+51999888777',
        ];

        $usersCountBefore = User::count();

        $response = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/personas', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'nombres' => 'Pedro',
                    'apellidos' => 'Castillo',
                    'nombre_completo' => 'Pedro Castillo',
                    'tipo_documento' => 'DNI',
                    'numero_documento' => '88776655',
                    'email' => 'pedro@example.com',
                    'telefono' => '+51999888777',
                    'estado' => 'activo',
                    'has_user' => false,
                ],
            ]);

        $this->assertDatabaseHas('personas', [
            'nombres' => 'Pedro',
            'apellidos' => 'Castillo',
            'numero_documento' => '88776655',
            'estado' => 'activo',
        ]);

        // Cero cuentas User creadas
        $this->assertSame($usersCountBefore, User::count());
    }

    /**
     * Un usuario sin permiso personas.editar recibe 403 al intentar editar.
     */
    public function test_user_without_personas_editar_is_forbidden(): void
    {
        $persona = Persona::create([
            'nombres' => 'Rosa',
            'apellidos' => 'Flores',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $this->actingAs($this->unprivilegedUser, 'web')
            ->putJson("/api/admin/personas/{$persona->id}", [
                'nombres' => 'Rosa María',
                'apellidos' => 'Flores',
                'estado' => 'inactivo',
            ])
            ->assertStatus(403);
    }

    /**
     * Un usuario con permiso personas.editar actualiza exitosamente una Persona.
     * REGLA VINCULANTE: Editar Persona NO altera registros de cuentas User.
     */
    public function test_user_with_personas_editar_can_update_persona(): void
    {
        $persona = Persona::create([
            'nombres' => 'Rosa',
            'apellidos' => 'Flores',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $userCountBefore = User::count();

        $response = $this->actingAs($this->adminUser, 'web')
            ->putJson("/api/admin/personas/{$persona->id}", [
                'nombres' => 'Rosa Elena',
                'apellidos' => 'Flores Soto',
                'tipo_documento' => 'Pasaporte',
                'numero_documento' => 'P123456',
                'email' => 'rosa@example.com',
                'telefono' => '123456789',
                'estado' => 'inactivo',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $persona->id,
                    'nombres' => 'Rosa Elena',
                    'apellidos' => 'Flores Soto',
                    'nombre_completo' => 'Rosa Elena Flores Soto',
                    'tipo_documento' => 'Pasaporte',
                    'numero_documento' => 'P123456',
                    'estado' => 'inactivo',
                ],
            ]);

        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'nombres' => 'Rosa Elena',
            'estado' => 'inactivo',
        ]);

        // Cero mutaciones en tabla users
        $this->assertSame($userCountBefore, User::count());
    }

    /**
     * Validación backend rechaza campos requeridos faltantes con 422.
     */
    public function test_validation_errors_return_422(): void
    {
        $response = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/personas', [
                'nombres' => '',
                'apellidos' => '',
                'email' => 'correo-invalido',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombres', 'apellidos', 'email']);
    }

    /**
     * Detección de duplicidad documental retorna 422 cuando tipo y número coinciden.
     */
    public function test_duplicate_document_returns_422(): void
    {
        Persona::create([
            'nombres' => 'Original',
            'apellidos' => 'Persona',
            'tipo_documento' => 'DNI',
            'numero_documento' => '77889900',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $response = $this->actingAs($this->adminUser, 'web')
            ->postJson('/api/admin/personas', [
                'nombres' => 'Duplicado',
                'apellidos' => 'Persona',
                'tipo_documento' => 'DNI',
                'numero_documento' => '77889900',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['numero_documento']);
    }

    /**
     * La paginación en servidor respeta page y per_page dentro de límites.
     */
    public function test_server_side_pagination_and_limits(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Persona::create([
                'nombres' => "Persona {$i}",
                'apellidos' => "Apellido {$i}",
                'estado' => PersonaStatus::ACTIVO,
            ]);
        }

        $response = $this->actingAs($this->adminUser, 'web')
            ->getJson('/api/admin/personas?page=2&per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonCount(10, 'data');
    }

    /**
     * La búsqueda filtra adecuadamente por nombres, apellidos, documento o email.
     */
    public function test_search_filters_results_correctly(): void
    {
        Persona::create([
            'nombres' => 'Alberto',
            'apellidos' => 'Fujimori',
            'tipo_documento' => 'DNI',
            'numero_documento' => '11223344',
            'email' => 'alberto@example.com',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        Persona::create([
            'nombres' => 'Beatriz',
            'apellidos' => 'Merino',
            'tipo_documento' => 'DNI',
            'numero_documento' => '55667788',
            'email' => 'beatriz@example.com',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        $response = $this->actingAs($this->adminUser, 'web')
            ->getJson('/api/admin/personas?search=Merino');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.apellidos', 'Merino');

        $responseDoc = $this->actingAs($this->adminUser, 'web')
            ->getJson('/api/admin/personas?search=11223344');

        $responseDoc->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombres', 'Alberto');
    }

    /**
     * Ordenamiento arbitrario no causa SQL injection ni error de base de datos;
     * el controlador hace fallback seguro a 'id'.
     */
    public function test_invalid_sort_falls_back_safely_without_sql_error(): void
    {
        $response = $this->actingAs($this->adminUser, 'web')
            ->getJson('/api/admin/personas?sort=password;DROP TABLE personas;--&direction=asc');

        $response->assertStatus(200);
    }

    /**
     * Un usuario con rol 'admin' pero SIN el permiso personas.ver asignado a su rol es DENEGADO (403).
     * Confirma la eliminación de privilegios implícitos por slug de rol.
     */
    public function test_admin_role_without_permission_is_denied(): void
    {
        $emptyAdminRole = Role::create([
            'name' => 'Admin Vacio',
            'slug' => 'admin_vacio',
            'is_system' => true,
        ]);

        $user = User::create([
            'name' => 'Admin Sin Permisos',
            'email' => 'adminvacio@test.local',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);
        $user->assignRole($emptyAdminRole);

        $this->actingAs($user, 'web')
            ->getJson('/api/admin/personas')
            ->assertStatus(403);
    }

    /**
     * Peticiones consecutivas autenticadas de detalle y edición mantienen la sesión
     * sin regresión de Unauthenticated (Fase 1C.1B).
     */
    public function test_consecutive_authenticated_requests_maintain_session_on_detail_and_update(): void
    {
        $persona = Persona::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Mendoza',
            'tipo_documento' => 'DNI',
            'numero_documento' => '33445566',
            'estado' => PersonaStatus::ACTIVO,
        ]);

        // 1. Listado inicial
        $this->actingAs($this->adminUser, 'web')
            ->getJson('/api/admin/personas')
            ->assertStatus(200);

        // 2. Consulta de detalle (Ver ficha)
        $this->getJson("/api/admin/personas/{$persona->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.nombres', 'Carlos');

        // 3. Edición (Guardar cambios)
        $this->putJson("/api/admin/personas/{$persona->id}", [
            'nombres' => 'Carlos Alberto',
            'apellidos' => 'Mendoza',
            'estado' => 'activo',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.nombres', 'Carlos Alberto');

        // 4. Consulta de detalle post-actualización
        $this->getJson("/api/admin/personas/{$persona->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.nombres', 'Carlos Alberto');
    }
}
