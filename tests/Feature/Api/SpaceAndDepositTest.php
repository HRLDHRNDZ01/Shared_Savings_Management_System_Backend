<?php

namespace Tests\Feature\Api;

use App\Enums\TransactionType;
use App\Models\Space;
use App\Models\SpaceMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceAndDepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_personal_space(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/spaces', [
                'name' => 'Emergency Fund',
                'type' => 'personal',
                'target_amount' => 10000,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Emergency Fund')
            ->assertJsonPath('data.type', 'personal')
            ->assertJsonPath('data.balance', '0.00');

        $spaceId = $response->json('data.space_id');

        $this->assertDatabaseHas('tbl_spaces', [
            'space_id' => $spaceId,
            'user_id' => $user->user_id,
            'name' => 'Emergency Fund',
            'type' => 'personal',
        ]);

        $this->assertDatabaseHas('tbl_space_members', [
            'space_id' => $spaceId,
            'user_id' => $user->user_id,
            'role' => 'owner',
        ]);
    }

    public function test_user_can_create_shared_space(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/spaces', [
                'name' => 'Family Fund',
                'type' => 'shared',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'shared');
    }

    public function test_user_can_add_deposit(): void
    {
        $user = User::factory()->create();
        $space = Space::query()->create([
            'user_id' => $user->user_id,
            'name' => 'Vacation',
            'type' => 'personal',
            'balance' => 0,
            'status' => 'active',
        ]);

        SpaceMember::query()->create([
            'space_id' => $space->space_id,
            'user_id' => $user->user_id,
            'role' => 'owner',
        ]);

        $this->actingAs($user)
            ->postJson('/api/transactions/deposit', [
                'space_id' => $space->space_id,
                'amount' => 500,
                'note' => 'Initial deposit',
            ])
            ->assertCreated()
            ->assertJsonPath('data.transaction.type', TransactionType::Deposit->value)
            ->assertJsonPath('data.space.balance', '500.00');

        $this->assertDatabaseHas('tbl_transactions', [
            'user_id' => $user->user_id,
            'space_id' => $space->space_id,
            'type' => 'deposit',
            'amount' => 500,
        ]);

        $this->assertDatabaseHas('tbl_notifications', [
            'user_id' => $user->user_id,
            'type' => 'deposit',
        ]);
    }

    public function test_user_can_withdraw(): void
    {
        $user = User::factory()->create();
        $space = Space::query()->create([
            'user_id' => $user->user_id,
            'name' => 'Vacation',
            'type' => 'personal',
            'balance' => 500,
            'status' => 'active',
        ]);

        SpaceMember::query()->create([
            'space_id' => $space->space_id,
            'user_id' => $user->user_id,
            'role' => 'owner',
        ]);

        $this->actingAs($user)
            ->postJson('/api/transactions/withdrawal', [
                'space_id' => $space->space_id,
                'amount' => 200,
                'note' => 'Emergency cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.transaction.type', TransactionType::Withdrawal->value)
            ->assertJsonPath('data.space.balance', '300.00');
    }

    public function test_user_cannot_withdraw_more_than_balance(): void
    {
        $user = User::factory()->create();
        $space = Space::query()->create([
            'user_id' => $user->user_id,
            'name' => 'Vacation',
            'type' => 'personal',
            'balance' => 100,
            'status' => 'active',
        ]);

        SpaceMember::query()->create([
            'space_id' => $space->space_id,
            'user_id' => $user->user_id,
            'role' => 'owner',
        ]);

        $this->actingAs($user)
            ->postJson('/api/transactions/withdrawal', [
                'space_id' => $space->space_id,
                'amount' => 200,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }
}
