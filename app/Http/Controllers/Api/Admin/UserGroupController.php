<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreUserGroupRequest;
use App\Http\Requests\Api\Admin\SyncGroupMenusRequest;
use App\Http\Requests\Api\Admin\UpdateUserGroupRequest;
use App\Http\Requests\Api\Admin\UpdateUserGroupAssignmentRequest;
use App\Models\SidebarMenu;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{
    public function index(): JsonResponse
    {
        $groups = UserGroup::query()
            ->withCount('users')
            ->with(['sidebarMenus:sidebar_menu_id,key,label,route_name,sort_order'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $groups,
        ]);
    }

    public function store(StoreUserGroupRequest $request): JsonResponse
    {
        $group = UserGroup::query()->create([
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->filled('menu_ids')) {
            $group->sidebarMenus()->sync($request->input('menu_ids', []));
        }

        return response()->json([
            'message' => 'User group created.',
            'data' => $group->load(['sidebarMenus', 'users:user_id,name,email,user_group_id']),
        ], 201);
    }

    public function show(UserGroup $userGroup): JsonResponse
    {
        return response()->json([
            'data' => $userGroup->load([
                'sidebarMenus',
                'users:user_id,name,email,role,user_group_id',
            ]),
        ]);
    }

    public function update(UpdateUserGroupRequest $request, UserGroup $userGroup): JsonResponse
    {
        $userGroup->update($request->validated());

        return response()->json([
            'message' => 'User group updated.',
            'data' => $userGroup->fresh()->load('sidebarMenus'),
        ]);
    }

    public function destroy(UserGroup $userGroup): JsonResponse
    {
        $userGroup->users()->update(['user_group_id' => null]);
        $userGroup->delete();

        return response()->json([
            'message' => 'User group deleted.',
        ]);
    }

    public function syncMenus(SyncGroupMenusRequest $request, UserGroup $userGroup): JsonResponse
    {
        $menuIds = collect($request->input('menu_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Maintenance is admin-only; never assign via group ticks.
        $allowedIds = SidebarMenu::query()
            ->whereIn('sidebar_menu_id', $menuIds)
            ->where('key', '!=', 'maintenance')
            ->pluck('sidebar_menu_id');

        $userGroup->sidebarMenus()->sync($allowedIds);

        return response()->json([
            'message' => 'Group menus updated.',
            'data' => $userGroup->fresh()->load('sidebarMenus'),
        ]);
    }

    public function menus(): JsonResponse
    {
        $menus = SidebarMenu::query()
            ->where('is_active', true)
            ->where('key', '!=', 'maintenance')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $menus,
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('userGroup:user_group_id,name')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%'.$request->string('q')->toString().'%';
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', $q)
                        ->orWhere('email', 'like', $q);
                });
            })
            ->orderBy('name')
            ->limit(100)
            ->get(['user_id', 'name', 'email', 'role', 'user_group_id']);

        return response()->json([
            'data' => $users,
        ]);
    }

    public function assignUser(UpdateUserGroupAssignmentRequest $request, User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Admin users do not use sidebar groups.',
            ], 422);
        }

        $user->update([
            'user_group_id' => $request->input('user_group_id'),
        ]);

        return response()->json([
            'message' => 'User group assignment updated.',
            'data' => $user->fresh()->load('userGroup:user_group_id,name'),
        ]);
    }
}
