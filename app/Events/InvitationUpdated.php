<?php

namespace App\Events;

use App\Models\SpaceInvitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SpaceInvitation $invitation)
    {
        $this->invitation->loadMissing([
            'space:space_id,name,type',
            'inviter:user_id,name,email',
            'invitedUser:user_id,name,email',
        ]);
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->invitation->invited_user_id),
            new PrivateChannel('users.'.$this->invitation->invited_by),
        ];
    }

    public function broadcastAs(): string
    {
        return 'invitation.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'invitation' => $this->invitation,
        ];
    }
}
