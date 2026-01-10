<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/verify', [AuthController::class, 'verifyEmail'])->name('verify.email');

// Google OAuth routes
Route::get('/api/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/api/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Facebook OAuth routes
Route::get('/api/auth/facebook', [AuthController::class, 'redirectToFacebook'])->name('auth.facebook');
Route::get('/api/auth/facebook/callback', [AuthController::class, 'handleFacebookCallback'])->name('auth.facebook.callback');

// Test route for Google OAuth config
Route::get('/test-google-config', function() {
    try {
        $config = config('services.google');
        return response()->json([
            'config_exists' => !empty($config),
            'client_id_set' => !empty($config['client_id']),
            'client_secret_set' => !empty($config['client_secret']),
            'redirect_set' => !empty($config['redirect']),
            'client_id_preview' => substr($config['client_id'] ?? '', 0, 20) . '...',
            'redirect_url' => $config['redirect'] ?? null
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Catch-all route để serve Vue.js app cho client-side routing
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
