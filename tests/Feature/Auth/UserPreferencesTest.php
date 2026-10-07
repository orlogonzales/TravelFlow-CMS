<?php

namespace Tests\Feature\Auth;

use App\Domains\User\Enums\UserStatus;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        static $counter = 1;

        return User::create(array_merge([
            'name' => 'Usuario Test ' . $counter,
            'email' => 'user' . ($counter++) . '@travelflow.pe',
            'password' => Hash::make('password12345'),
            'status' => UserStatus::ACTIVE,
            'ui_preferences' => null,
        ], $attributes));
    }

    public function test_unauthenticated_guest_cannot_access_preferences(): void
    {
        $this->getJson('/api/auth/preferences')
            ->assertStatus(401);

        $this->patchJson('/api/auth/preferences', ['theme' => 'dark'])
            ->assertStatus(401);

        $this->postJson('/api/auth/preferences/reset')
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_get_default_preferences(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->getJson('/api/auth/preferences')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'preferences' => [
                    'theme' => 'system',
                    'semi_dark' => true,
                    'sidebar_collapsed' => false,
                    'content_layout' => 'compact',
                    'navbar_type' => 'sticky',
                ],
            ]);
    }

    public function test_authenticated_user_can_update_their_own_preferences(): void
    {
        $user = $this->createUser();

        $payload = [
            'theme' => 'dark',
            'semi_dark' => false,
            'sidebar_collapsed' => true,
            'content_layout' => 'wide',
            'navbar_type' => 'static',
        ];

        $this->actingAs($user, 'web')
            ->patchJson('/api/auth/preferences', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'preferences' => [
                    'theme' => 'dark',
                    'semi_dark' => false,
                    'sidebar_collapsed' => true,
                    'content_layout' => 'wide',
                    'navbar_type' => 'static',
                ],
            ]);

        $user->refresh();
        $this->assertEquals('dark', $user->ui_preferences['theme']);
        $this->assertFalse($user->ui_preferences['semi_dark']);
        $this->assertTrue($user->ui_preferences['sidebar_collapsed']);
        $this->assertEquals('wide', $user->ui_preferences['content_layout']);
        $this->assertEquals('static', $user->ui_preferences['navbar_type']);
    }

    public function test_partial_update_merges_with_existing_custom_preferences(): void
    {
        $user = $this->createUser([
            'ui_preferences' => [
                'theme' => 'dark',
                'sidebar_collapsed' => true,
            ],
        ]);

        $this->actingAs($user, 'web')
            ->patchJson('/api/auth/preferences', ['content_layout' => 'wide'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'preferences' => [
                    'theme' => 'dark',
                    'sidebar_collapsed' => true,
                    'content_layout' => 'wide',
                ],
            ]);

        $user->refresh();
        $this->assertEquals('dark', $user->ui_preferences['theme']);
        $this->assertTrue($user->ui_preferences['sidebar_collapsed']);
        $this->assertEquals('wide', $user->ui_preferences['content_layout']);
    }

    public function test_invalid_values_are_rejected_with_422(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->patchJson('/api/auth/preferences', [
                'theme' => 'neon-rainbow', // inválido
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);

        $this->actingAs($user, 'web')
            ->patchJson('/api/auth/preferences', [
                'content_layout' => 'ultra-fullscreen', // inválido
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content_layout']);

        $this->actingAs($user, 'web')
            ->patchJson('/api/auth/preferences', [
                'navbar_type' => 'floating-bubble', // inválido
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['navbar_type']);
    }

    public function test_user_can_reset_preferences_to_defaults(): void
    {
        $user = $this->createUser([
            'ui_preferences' => [
                'theme' => 'dark',
                'sidebar_collapsed' => true,
                'content_layout' => 'wide',
            ],
        ]);

        $this->actingAs($user, 'web')
            ->postJson('/api/auth/preferences/reset')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'preferences' => User::DEFAULT_UI_PREFERENCES,
            ]);

        $user->refresh();
        $this->assertNull($user->ui_preferences);
        $this->assertEquals(User::DEFAULT_UI_PREFERENCES, $user->getEffectiveUiPreferences());
    }

    public function test_preferences_are_strictly_isolated_between_users(): void
    {
        $userA = $this->createUser([
            'email' => 'usera@travelflow.pe',
            'ui_preferences' => ['theme' => 'dark'],
        ]);

        $userB = $this->createUser([
            'email' => 'userb@travelflow.pe',
            'ui_preferences' => ['theme' => 'light'],
        ]);

        // Usuario A actualiza sus preferencias
        $this->actingAs($userA, 'web')
            ->patchJson('/api/auth/preferences', [
                'theme' => 'system',
                'user_id' => $userB->id, // Intento malicioso de inyectar otro ID
            ])
            ->assertOk();

        $userA->refresh();
        $userB->refresh();

        // User A cambió a system
        $this->assertEquals('system', $userA->ui_preferences['theme']);
        // User B sigue intacto en light
        $this->assertEquals('light', $userB->ui_preferences['theme']);
    }

    public function test_inactive_or_blocked_user_is_forbidden_from_preferences(): void
    {
        $inactiveUser = $this->createUser([
            'status' => UserStatus::INACTIVE,
        ]);

        $this->actingAs($inactiveUser, 'web')
            ->getJson('/api/auth/preferences')
            ->assertStatus(403);

        $blockedUser = $this->createUser([
            'status' => UserStatus::BLOCKED,
        ]);

        $this->actingAs($blockedUser, 'web')
            ->patchJson('/api/auth/preferences', ['theme' => 'dark'])
            ->assertStatus(403);
    }
}
