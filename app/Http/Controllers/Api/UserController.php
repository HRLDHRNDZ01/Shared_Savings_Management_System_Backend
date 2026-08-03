<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $query = $validated['q'];
        $currentUserId = $request->user()->getKey();

        $users = User::query()
            ->select(['user_id', 'name', 'email', 'contact_number', 'role'])
            ->where('user_id', '!=', $currentUserId)
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $users,
        ]);
    }
}
