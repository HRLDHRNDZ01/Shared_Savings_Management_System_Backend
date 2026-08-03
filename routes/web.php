<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'SSMS API',
        'docs' => url('/api/health'),
    ]);
});
