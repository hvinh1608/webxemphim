<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Temporarily disable web routes - use API routes instead
// All routes moved to routes/api.php for production


// Đảm bảo callback Google dùng middleware web (bắt buộc cho Socialite)
Route::get('/api/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Simple fallback for any unmatched routes
Route::fallback(function () {
    return response()->json([
        'error' => 'Route not found',
        'message' => 'This is an API backend. Use /api/* endpoints.',
        'available_endpoints' => [
            'GET /api/test',
            'GET /api/web-test',
            'GET /api/movies',
            'GET /api/auth/google',
            'GET /api/auth/facebook'
        ]
    ], 404);
});
