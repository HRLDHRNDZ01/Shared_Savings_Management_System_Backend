<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonStructure([
                'message',
                'data' => ['user' => ['user_id', 'name', 'email', 'role'], 'token', 'token_type'],
            ]);

        $this->assertDatabaseHas('tbl_users', [
            'email' => 'test@example.com',
            'role' => 'user',
        ]);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/admin/ping')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/ping')
            ->assertOk()
            ->assertJsonPath('message', 'admin ok');
    }

    public function test_user_can_login_and_access_me(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.user.email', $user->email);

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->putJson('/api/auth/profile', [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('tbl_users', [
            'user_id' => $user->user_id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_can_update_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->putJson('/api/auth/profile', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
