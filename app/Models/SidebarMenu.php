<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'key',
    'label',
    'icon',
    'route_name',
    'sort_order',
    'is_active',
])]
class SidebarMenu extends Model
{
    protected $table = 'tbl_sidebar_menus';

    protected $primaryKey = 'sidebar_menu_id';

    public function getRouteKeyName(): string
    {
        return 'sidebar_menu_id';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            UserGroup::class,
            'tbl_group_sidebar_menus',
            'sidebar_menu_id',
            'user_group_id',
            'sidebar_menu_id',
            'user_group_id',
        )->withTimestamps();
    }
}
