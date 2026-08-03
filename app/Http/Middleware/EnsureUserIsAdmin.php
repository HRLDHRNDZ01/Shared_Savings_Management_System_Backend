<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== Role::Admin) {
            return response()->json([
                'message' => 'Forbidden. Admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
