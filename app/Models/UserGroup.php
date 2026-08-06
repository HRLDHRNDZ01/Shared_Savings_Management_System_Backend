<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'is_active',
])]
class UserGroup extends Model
{
    protected $table = 'tbl_user_groups';

    protected $primaryKey = 'user_group_id';

    public function getRouteKeyName(): string
    {
        return 'user_group_id';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sidebarMenus(): BelongsToMany
    {
        return $this->belongsToMany(
            SidebarMenu::class,
            'tbl_group_sidebar_menus',
            'user_group_id',
            'sidebar_menu_id',
            'user_group_id',
            'sidebar_menu_id',
        )->withTimestamps()
            ->orderBy('tbl_sidebar_menus.sort_order');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'user_group_id', 'user_group_id');
    }
}
