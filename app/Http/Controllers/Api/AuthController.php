<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'contact_number' => $request->input('contact_number'),
            'password' => $request->string('password')->toString(),
            'role' => Role::User,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully.',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($request->filled('password')) {
            if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }

            $user->password = $request->string('password')->toString();
        }

        if ($request->filled('name')) {
            $user->name = $request->string('name')->toString();
        }

        if ($request->filled('email')) {
            $user->email = $request->string('email')->toString();
        }

        if ($request->exists('contact_number')) {
            $user->contact_number = $request->input('contact_number');
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $user->fresh(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
