<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'space_id',
    'invited_by',
    'invited_user_id',
    'status',
])]
class SpaceInvitation extends Model
{
    protected $table = 'tbl_space_invitations';

    protected $primaryKey = 'space_invitation_id';

    public function getRouteKeyName(): string
    {
        return 'space_invitation_id';
    }

    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class, 'space_id', 'space_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by', 'user_id');
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id', 'user_id');
    }
}
