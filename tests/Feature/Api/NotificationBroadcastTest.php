<?php

namespace Tests\Feature\Api;

use App\Enums\NotificationType;
use App\Enums\SpaceMemberRole;
use App\Enums\SpaceStatus;
use App\Enums\SpaceType;
use App\Events\NotificationCreated;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_notification_dispatches_broadcast_event(): void
    {
        Event::fake([NotificationCreated::class]);

        $user = User::factory()->create();

        AppNotification::create([
            'user_id' => $user->getKey(),
            'title' => 'Deposit received',
            'message' => 'You deposited 100.00 into Emergency Fund.',
            'type' => NotificationType::Deposit,
            'action' => 'deposit',
            'amount' => 100,
            'is_read' => false,
        ]);

        Event::assertDispatched(NotificationCreated::class, function (NotificationCreated $event) use ($user) {
            return (int) $event->notification->user_id === (int) $user->getKey()
                && $event->broadcastAs() === 'notification.created'
                && $event->broadcastOn()[0]->name === 'private-users.'.$user->getKey();
        });
    }

    public function test_deposit_dispatches_notification_broadcast_event(): void
    {
        Event::fake([NotificationCreated::class]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $space = $user->spaces()->create([
            'name' => 'Emergency Fund',
            'type' => SpaceType::Personal,
            'target_amount' => 1000,
            'balance' => 0,
            'status' => SpaceStatus::Active,
        ]);

        $space->members()->create([
            'user_id' => $user->getKey(),
            'role' => SpaceMemberRole::Owner,
        ]);

        $this->postJson('/api/transactions/deposit', [
            'space_id' => $space->getKey(),
            'amount' => 50,
        ])->assertCreated();

        Event::assertDispatched(NotificationCreated::class);
        $this->assertDatabaseHas('tbl_notifications', [
            'user_id' => $user->getKey(),
            'type' => NotificationType::Deposit->value,
        ]);
    }
}
