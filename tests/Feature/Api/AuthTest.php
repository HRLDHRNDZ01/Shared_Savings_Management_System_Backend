<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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
            'username' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonPath('data.user.username', 'testuser')
            ->assertJsonPath('data.user.first_name', 'Test')
            ->assertJsonPath('data.user.last_name', 'User')
            ->assertJsonPath('data.user.name', 'Test User')
            ->assertJsonStructure([
                'message',
                'data' => ['user' => ['user_id', 'username', 'first_name', 'last_name', 'name', 'email', 'role'], 'token', 'token_type'],
            ]);

        $this->assertDatabaseHas('tbl_users', [
            'username' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
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
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.user.username', $user->username);

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.username', $user->username);
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
            'username' => 'olduser',
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->putJson('/api/auth/profile', [
                'username' => 'newuser',
                'first_name' => 'New',
                'last_name' => 'Name',
                'email' => 'new@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.username', 'newuser')
            ->assertJsonPath('data.first_name', 'New')
            ->assertJsonPath('data.last_name', 'Name')
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('tbl_users', [
            'user_id' => $user->user_id,
            'username' => 'newuser',
            'first_name' => 'New',
            'last_name' => 'Name',
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

    public function test_forgot_password_sends_reset_link_for_a_registered_email(): void
    {
        Notification::fake();

        config(['app.frontend_url' => 'https://frontend.test']);

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'Test@Example.com',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'If that email is registered, a password reset link has been sent.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'test@example.com',
        ]);

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($user): bool {
                $url = $notification->resetUrl($user->email);

                return str_contains($url, 'https://frontend.test/reset-password?')
                    && str_contains($url, 'email='.urlencode($user->email))
                    && str_contains($url, 'token=');
            }
        );
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'missing@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'If that email is registered, a password reset link has been sent.');

        $this->assertDatabaseCount('password_reset_tokens', 0);
        Notification::assertNothingSent();
    }

    public function test_forgot_password_is_throttled_for_repeat_requests(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user->createToken('api');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password has been reset.');

        $fresh = $user->fresh();

        $this->assertTrue(Hash::check('new-password', $fresh->password));
        $this->assertSame(0, $fresh->tokens()->count());
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'test@example.com',
        ]);

        $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'new-password',
        ])->assertOk();
    }

    public function test_reset_password_rejects_an_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => 'not-a-real-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
