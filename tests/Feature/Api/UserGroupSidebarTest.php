<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\SidebarMenu;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\SidebarAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGroupSidebarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SidebarAccessSeeder::class);
    }

    public function test_admin_sees_all_sidebar_menus_including_maintenance(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/me/sidebar')
            ->assertOk()
            ->assertJsonPath('data.is_admin', true)
            ->assertJsonFragment(['key' => 'maintenance'])
            ->assertJsonFragment(['key' => 'dashboard']);
    }

    public function test_user_only_sees_group_checked_menus(): void
    {
        $group = UserGroup::query()->where('name', 'Standard User')->firstOrFail();
        $dashboard = SidebarMenu::query()->where('key', 'dashboard')->firstOrFail();
        $spaces = SidebarMenu::query()->where('key', 'spaces')->firstOrFail();

        $group->sidebarMenus()->sync([$dashboard->sidebar_menu_id, $spaces->sidebar_menu_id]);

        $user = User::factory()->create([
            'role' => Role::User,
            'user_group_id' => $group->user_group_id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/me/sidebar');

        $response->assertOk()
            ->assertJsonPath('data.is_admin', false)
            ->assertJsonCount(2, 'data.menus')
            ->assertJsonFragment(['key' => 'dashboard'])
            ->assertJsonFragment(['key' => 'spaces'])
            ->assertJsonMissing(['key' => 'maintenance']);
    }

    public function test_admin_can_sync_group_menus_and_assign_user(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UserGroup::query()->create([
            'name' => 'Limited',
            'description' => 'Few menus',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['role' => Role::User]);
        $menuIds = SidebarMenu::query()
            ->whereIn('key', ['dashboard', 'notifications'])
            ->pluck('sidebar_menu_id')
            ->all();

        $this->actingAs($admin)
            ->putJson("/api/admin/user-groups/{$group->user_group_id}/menus", [
                'menu_ids' => $menuIds,
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data.sidebar_menus');

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$user->user_id}/group", [
                'user_group_id' => $group->user_group_id,
            ])
            ->assertOk()
            ->assertJsonPath('data.user_group_id', $group->user_group_id);

        $this->actingAs($user->fresh())
            ->getJson('/api/me/sidebar')
            ->assertOk()
            ->assertJsonCount(2, 'data.menus')
            ->assertJsonFragment(['key' => 'dashboard'])
            ->assertJsonFragment(['key' => 'notifications']);
    }

    public function test_non_admin_cannot_manage_groups(): void
    {
        $user = User::factory()->create(['role' => Role::User]);

        $this->actingAs($user)
            ->getJson('/api/admin/user-groups')
            ->assertForbidden();
    }
}
