<?php

namespace App\Models;

use App\Enums\SpaceStatus;
use App\Enums\SpaceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'name',
    'type',
    'target_amount',
    'balance',
    'status',
])]
class Space extends Model
{
    protected $table = 'tbl_spaces';

    protected $primaryKey = 'space_id';

    public function getRouteKeyName(): string
    {
        return 'space_id';
    }

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'status' => SpaceStatus::class,
            'type' => SpaceType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(SpaceMember::class, 'space_id', 'space_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(SpaceInvitation::class, 'space_id', 'space_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'space_id', 'space_id');
    }
}
