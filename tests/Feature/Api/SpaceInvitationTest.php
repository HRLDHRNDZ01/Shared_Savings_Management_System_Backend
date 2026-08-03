<?php

namespace Tests\Feature\Api;

use App\Enums\SpaceMemberRole;
use App\Enums\SpaceType;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\SpaceMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_user_to_shared_space(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'friend@example.com']);

        $space = Space::query()->create([
            'user_id' => $owner->user_id,
            'name' => 'Family Fund',
            'type' => SpaceType::Shared,
            'balance' => 0,
            'status' => 'active',
        ]);

        SpaceMember::query()->create([
            'space_id' => $space->space_id,
            'user_id' => $owner->user_id,
            'role' => SpaceMemberRole::Owner,
        ]);

        $this->actingAs($owner)
            ->postJson("/api/spaces/{$space->space_id}/invitations", [
                'email' => 'friend@example.com',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('tbl_space_invitations', [
            'space_id' => $space->space_id,
            'invited_user_id' => $invitee->user_id,
            'status' => 'pending',
        ]);
    }

    public function test_invitee_can_accept_invitation(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();

        $space = Space::query()->create([
            'user_id' => $owner->user_id,
            'name' => 'Family Fund',
            'type' => SpaceType::Shared,
            'balance' => 0,
            'status' => 'active',
        ]);

        SpaceMember::query()->create([
            'space_id' => $space->space_id,
            'user_id' => $owner->user_id,
            'role' => SpaceMemberRole::Owner,
        ]);

        $invitation = SpaceInvitation::query()->create([
            'space_id' => $space->space_id,
            'invited_by' => $owner->user_id,
            'invited_user_id' => $invitee->user_id,
            'status' => 'pending',
        ]);

        $this->actingAs($invitee)
            ->postJson("/api/invitations/{$invitation->space_invitation_id}/accept")
            ->assertOk()
            ->assertJsonPath('message', 'Invitation accepted.');

        $this->assertDatabaseHas('tbl_space_members', [
            'space_id' => $space->space_id,
            'user_id' => $invitee->user_id,
            'role' => 'member',
        ]);
    }
}
