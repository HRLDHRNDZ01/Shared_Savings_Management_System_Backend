<?php

namespace App\Http\Controllers\Api;

use App\Enums\SpaceMemberRole;
use App\Enums\SpaceStatus;
use App\Enums\SpaceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSpaceRequest;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->getKey();

        $spaces = Space::query()
            ->where('status', SpaceStatus::Active)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId));
            })
            ->latest()
            ->get();

        return response()->json([
            'data' => [
                'spaces' => $spaces,
                'space_count' => $spaces->count(),
                'total_balance' => $spaces->sum(fn ($space) => (float) $space->balance),
            ],
        ]);
    }

    public function store(StoreSpaceRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->enum('type', SpaceType::class) ?? SpaceType::Personal;

        $space = DB::transaction(function () use ($request, $user, $type) {
            $space = $user->spaces()->create([
                'name' => $request->string('name')->toString(),
                'type' => $type,
                'target_amount' => $request->input('target_amount'),
                'balance' => 0,
                'status' => SpaceStatus::Active,
            ]);

            $space->members()->create([
                'user_id' => $user->getKey(),
                'role' => SpaceMemberRole::Owner,
            ]);

            return $space->load('members');
        });

        return response()->json([
            'message' => 'Space created successfully.',
            'data' => $space,
        ], 201);
    }

    public function members(Request $request, Space $space): JsonResponse
    {
        $userId = $request->user()->getKey();

        $canView = $space->user_id === $userId
            || $space->members()->where('user_id', $userId)->exists();

        if (! $canView) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $members = $space->members()
            ->with('user:user_id,name,email,contact_number,role')
            ->orderBy('role')
            ->get()
            ->map(fn ($member) => [
                'space_member_id' => $member->space_member_id,
                'space_id' => $member->space_id,
                'role' => $member->role,
                'user' => $member->user,
                'joined_at' => $member->created_at,
            ]);

        return response()->json([
            'data' => [
                'space' => [
                    'space_id' => $space->space_id,
                    'name' => $space->name,
                    'type' => $space->type,
                ],
                'members' => $members,
                'member_count' => $members->count(),
            ],
        ]);
    }
}
