<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_other_users(): void
    {
        $me = User::factory()->create(['name' => 'Harold']);
        User::factory()->create(['name' => 'Danica Cochoco', 'email' => 'danica@example.com']);
        User::factory()->create(['name' => 'Other Person', 'email' => 'other@example.com']);

        $this->actingAs($me)
            ->getJson('/api/users/search?q=Danica')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Danica Cochoco')
            ->assertJsonMissing(['name' => 'Harold']);
    }

    public function test_search_requires_query(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)
            ->getJson('/api/users/search')
            ->assertStatus(422);
    }
}
