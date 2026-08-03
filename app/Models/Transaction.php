<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'space_id',
    'type',
    'amount',
    'note',
    'occurred_at',
])]
class Transaction extends Model
{
    protected $table = 'tbl_transactions';

    protected $primaryKey = 'transaction_id';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'type' => TransactionType::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class, 'space_id', 'space_id');
    }
}
