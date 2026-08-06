<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\SidebarMenu;
use App\Models\UserGroup;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'contact_number', 'password', 'role', 'user_group_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'tbl_users';

    protected $primaryKey = 'user_id';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isUser(): bool
    {
        return $this->role === Role::User;
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id', 'user_group_id');
    }

    /**
     * @return Collection<int, SidebarMenu>
     */
    public function allowedSidebarMenus(): Collection
    {
        if ($this->isAdmin()) {
            return SidebarMenu::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        if (! $this->user_group_id) {
            return collect();
        }

        $group = $this->relationLoaded('userGroup')
            ? $this->userGroup
            : $this->userGroup()->with('sidebarMenus')->first();

        if (! $group || ! $group->is_active) {
            return collect();
        }

        return $group->sidebarMenus
            ->where('is_active', true)
            ->where('key', '!=', 'maintenance')
            ->values();
    }

    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class, 'user_id', 'user_id');
    }

    public function spaceMemberships(): HasMany
    {
        return $this->hasMany(SpaceMember::class, 'user_id', 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'user_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class, 'user_id', 'user_id');
    }
}
