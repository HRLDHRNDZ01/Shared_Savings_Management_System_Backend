<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'space_id',
    'actor_user_id',
    'title',
    'message',
    'type',
    'action',
    'amount',
    'is_read',
])]
class AppNotification extends Model
{
    protected $table = 'tbl_notifications';

    protected $primaryKey = 'notification_id';

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'type' => NotificationType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'user_id');
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class, 'space_id', 'space_id');
    }
}
