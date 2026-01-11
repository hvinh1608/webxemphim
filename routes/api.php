<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

// Auth routes (không cần auth)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// Protected routes (cần auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
});

// Test route for debugging
Route::get('test', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Laravel API is working!',
        'timestamp' => now(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'env' => config('app.env'),
        'debug' => config('app.debug'),
        'url' => config('app.url')
    ]);
});

// Public routes
Route::apiResource('movies', MovieController::class);
Route::apiResource('episodes', EpisodeController::class)->middleware('auth.api');
Route::get('movies/{movie}/related', [MovieController::class, 'related']);
Route::get('movies/{movie}/episodes', [MovieController::class, 'episodes'])->middleware('auth.api');
Route::get('search', [MovieController::class, 'search']);
Route::post('resend-verification', [AuthController::class, 'resendVerification']);
