<?php

namespace App\Models;

use App\Enums\SpaceMemberRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'space_id',
    'user_id',
    'role',
])]
class SpaceMember extends Model
{
    protected $table = 'tbl_space_members';

    protected $primaryKey = 'space_member_id';

    protected function casts(): array
    {
        return [
            'role' => SpaceMemberRole::class,
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class, 'space_id', 'space_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
