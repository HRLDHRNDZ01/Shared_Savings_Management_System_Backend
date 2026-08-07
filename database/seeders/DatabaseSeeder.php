<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Uses direct creates (no factories) so production `composer --no-dev`
     * deploys work without fakerphp/faker.
     */
    public function run(): void
    {
        $this->call(SidebarAccessSeeder::class);

        $standardGroupId = UserGroup::query()
            ->where('name', 'Standard User')
            ->value('user_group_id');

        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => Role::Admin,
                'user_group_id' => null,
                'email_verified_at' => now(),
            ],
        );

        User::query()->firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'role' => Role::User,
                'user_group_id' => $standardGroupId,
                'email_verified_at' => now(),
            ],
        );
    }
}
