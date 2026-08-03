<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Controllers\Api\SpaceInvitationController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/profile', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/search', [UserController::class, 'search']);

    Route::get('/spaces', [SpaceController::class, 'index']);
    Route::post('/spaces', [SpaceController::class, 'store']);
    Route::get('/spaces/{space}/members', [SpaceController::class, 'members']);
    Route::post('/spaces/{space}/invitations', [SpaceInvitationController::class, 'store']);

    Route::get('/invitations', [SpaceInvitationController::class, 'index']);
    Route::post('/invitations/{invitation}/accept', [SpaceInvitationController::class, 'accept']);
    Route::post('/invitations/{invitation}/decline', [SpaceInvitationController::class, 'decline']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions/deposit', [TransactionController::class, 'storeDeposit']);
    Route::post('/transactions/withdrawal', [TransactionController::class, 'storeWithdrawal']);
    Route::post('/transactions/withdraw', [TransactionController::class, 'storeWithdrawal']);
    Route::get('/notifications', [NotificationController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/ping', function () {
        return response()->json(['message' => 'admin ok']);
    });
});
