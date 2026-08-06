<?php

namespace Database\Seeders;

use App\Models\SidebarMenu;
use App\Models\UserGroup;
use Illuminate\Database\Seeder;

class SidebarAccessSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => '🏠', 'route_name' => 'dashboard', 'sort_order' => 10],
            ['key' => 'spaces', 'label' => 'Savings Spaces', 'icon' => '💰', 'route_name' => 'spaces', 'sort_order' => 20],
            ['key' => 'transactions', 'label' => 'Transactions', 'icon' => '📜', 'route_name' => 'transactions', 'sort_order' => 30],
            ['key' => 'notifications', 'label' => 'Notifications', 'icon' => '🔔', 'route_name' => 'notifications', 'sort_order' => 40],
            ['key' => 'reports', 'label' => 'Reports', 'icon' => '📊', 'route_name' => 'reports', 'sort_order' => 50],
            ['key' => 'profile', 'label' => 'Profile', 'icon' => '👤', 'route_name' => 'profile', 'sort_order' => 60],
            ['key' => 'settings', 'label' => 'Settings', 'icon' => '⚙', 'route_name' => 'settings', 'sort_order' => 70],
            ['key' => 'maintenance', 'label' => 'Maintenance', 'icon' => '🛠', 'route_name' => 'maintenance', 'sort_order' => 100],
        ];

        foreach ($menus as $menu) {
            SidebarMenu::query()->updateOrCreate(
                ['key' => $menu['key']],
                [...$menu, 'is_active' => true],
            );
        }

        $standard = UserGroup::query()->updateOrCreate(
            ['name' => 'Standard User'],
            [
                'description' => 'Default access for regular users.',
                'is_active' => true,
            ],
        );

        $standardMenuIds = SidebarMenu::query()
            ->where('is_active', true)
            ->where('key', '!=', 'maintenance')
            ->pluck('sidebar_menu_id');

        $standard->sidebarMenus()->sync($standardMenuIds);
    }
}
