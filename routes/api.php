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

// Public routes
Route::apiResource('movies', MovieController::class);
Route::apiResource('episodes', EpisodeController::class)->middleware('auth.api');
Route::get('movies/{movie}/related', [MovieController::class, 'related']);
Route::get('movies/{movie}/episodes', [MovieController::class, 'episodes'])->middleware('auth.api');
Route::get('search', [MovieController::class, 'search']);
Route::post('resend-verification', [AuthController::class, 'resendVerification']);
