<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('userGroup.sidebarMenus');

        $menus = $user->allowedSidebarMenus()->map(fn ($menu) => [
            'sidebar_menu_id' => $menu->sidebar_menu_id,
            'key' => $menu->key,
            'label' => $menu->label,
            'icon' => $menu->icon,
            'route_name' => $menu->route_name,
            'sort_order' => $menu->sort_order,
        ])->values();

        return response()->json([
            'data' => [
                'menus' => $menus,
                'user_group' => $user->userGroup
                    ? [
                        'user_group_id' => $user->userGroup->user_group_id,
                        'name' => $user->userGroup->name,
                    ]
                    : null,
                'is_admin' => $user->isAdmin(),
            ],
        ]);
    }
}
