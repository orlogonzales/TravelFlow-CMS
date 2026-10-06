<?php

namespace Tests\Feature;

use App\Domains\User\Enums\UserStatus;
use App\Domains\User\Models\Permission;
use App\Domains\User\Models\Persona;
use App\Domains\User\Models\Role;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica autenticación exitosa mediante POST /api/auth/login.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $persona = Persona::create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'tipo_documento' => 'DNI',
            'numero_documento' => '87654321',
        ]);

        $role = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $perm = Permission::create([
            'name' => 'Ver Usuarios',
            'slug' => 'usuarios.ver',
            'domain' => 'usuario',
        ]);
        $role->givePermission($perm);

        $user = User::create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => Hash::make('password123'),
            'persona_id' => $persona->id,
            'status' => UserStatus::ACTIVE,
        ]);
        $user->assignRole($role);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Autenticación exitosa',
                'user' => [
                    'id' => $user->id,
                    'email' => 'juan@example.com',
                    'status' => 'active',
                    'persona' => [
                        'nombre_completo' => 'Juan Pérez',
                    ],
                    'roles' => ['editor'],
                    'permissions' => ['usuarios.ver'],
                ],
            ]);

        $this->assertAuthenticatedAs($user);

        // Verifica que se registraron datos de último login
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
    }

    /**
     * Verifica que credenciales inválidas retornan error de validación.
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::create([
            'name' => 'Usuario Prueba',
            'email' => 'prueba@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'prueba@example.com',
            'password' => 'password_erroneo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    /**
     * Verifica que un usuario inactivo no puede autenticarse.
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'name' => 'Usuario Inactivo',
            'email' => 'inactivo@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::INACTIVE,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactivo@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    /**
     * Verifica que un usuario bloqueado no puede autenticarse.
     */
    public function test_blocked_user_cannot_login(): void
    {
        User::create([
            'name' => 'Usuario Bloqueado',
            'email' => 'bloqueado@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::BLOCKED,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'bloqueado@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    /**
     * Verifica el rate limiting ante múltiples intentos fallidos de login.
     */
    public function test_login_attempts_are_rate_limited(): void
    {
        User::create([
            'name' => 'Target RateLimit',
            'email' => 'target@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'target@example.com',
                'password' => 'bad_pass',
            ]);
        }

        // El 6to intento debe ser bloqueado por rate limit
        $response = $this->postJson('/api/auth/login', [
            'email' => 'target@example.com',
            'password' => 'bad_pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Verifica GET /api/auth/me para usuario autenticado.
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::create([
            'name' => 'Usuario Sesion',
            'email' => 'sesion@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => 'sesion@example.com',
                ],
            ]);
    }

    /**
     * Verifica GET /api/auth/me para invitado no autenticado.
     */
    public function test_unauthenticated_guest_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Verifica que el logout invalida la sesión y desautentica al usuario.
     */
    public function test_user_can_logout(): void
    {
        User::create([
            'name' => 'Usuario Logout',
            'email' => 'logout@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'logout@example.com',
            'password' => 'password123',
        ]);
        $this->assertAuthenticated();

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sesión cerrada correctamente',
            ]);

        $this->assertGuest();
    }

    /**
     * Verifica que el control de acceso backend responde HTTP 403 Forbidden
     * cuando el usuario carece del permiso correspondiente (Menú ≠ Autorización).
     */
    public function test_unauthorized_user_receives_403_forbidden(): void
    {
        $permView = Permission::create([
            'name' => 'Ver Usuarios',
            'slug' => 'usuarios.ver',
            'domain' => 'usuario',
        ]);

        $roleViewer = Role::create([
            'name' => 'Solo Lector',
            'slug' => 'solo_lector',
        ]);
        $roleViewer->givePermission($permView);

        $user = User::create([
            'name' => 'Lector Limitado',
            'email' => 'limitado@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);
        $user->assignRole($roleViewer);

        // Permitido: usuarios.ver -> HTTP 200
        $responseAllowed = $this->actingAs($user, 'web')->getJson('/api/test/usuarios');
        $responseAllowed->assertStatus(200)
            ->assertJson(['success' => true]);

        // Denegado: usuarios.gestionar -> HTTP 403 Forbidden
        $responseDenied = $this->actingAs($user, 'web')->postJson('/api/test/usuarios');
        $responseDenied->assertStatus(403);
    }

    /**
     * Verifica que el middleware EnsureUserIsActive desautentica de inmediato
     * a un usuario si su cuenta pasa a estado inactivo durante una sesión abierta.
     */
    public function test_user_session_is_terminated_if_account_becomes_inactive(): void
    {
        $user = User::create([
            'name' => 'Usuario Cambiante',
            'email' => 'cambiante@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'cambiante@example.com',
            'password' => 'password123',
        ]);
        $this->assertAuthenticated();

        // Se inactiva la cuenta en la base de datos
        $user->update(['status' => UserStatus::INACTIVE]);

        // La siguiente petición debe ser rechazada con 403 y la sesión cerrada
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertGuest();
    }
}
