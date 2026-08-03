<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->appNotifications()
            ->with([
                'actor:user_id,name,email',
                'space:space_id,name,type',
            ])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'data' => [
                'recent_notifications' => $notifications,
                'unread_count' => $notifications->where('is_read', false)->count(),
            ],
        ]);
    }
}
