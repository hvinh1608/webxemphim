<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Auth routes (không cần auth)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// OAuth routes (không cần auth)
Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('auth/facebook', [AuthController::class, 'redirectToFacebook']);
Route::get('auth/facebook/callback', [AuthController::class, 'handleFacebookCallback']);

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

// Debug environment and Laravel setup
Route::get('env-check', function () {
    return response()->json([
        'app_name' => config('app.name'),
        'app_env' => config('app.env'),
        'app_debug' => config('app.debug'),
        'app_url' => config('app.url'),
        'app_key' => config('app.key') ? 'SET (' . strlen(config('app.key')) . ' chars)' : 'NOT SET',
        'db_connection' => config('database.default'),
        'db_host' => config('database.connections.pgsql.host'),
        'db_database' => config('database.connections.pgsql.database'),
        'cache_driver' => config('cache.default'),
        'session_driver' => config('session.driver'),
        'log_channel' => config('logging.default'),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]);
});

// Simple debug route
Route::get('debug-db', function () {
    return response()->json([
        'status' => 'DEBUG ROUTE WORKING',
        'database_url' => env('DATABASE_URL') ?: 'NOT SET',
        'db_connection' => config('database.default'),
        'db_host' => config('database.connections.pgsql.host'),
        'timestamp' => now()
    ]);
});

// Check startup script log
Route::get('debug-log', function () {
    $logContent = file_exists('/tmp/db-config.log') ? file_get_contents('/tmp/db-config.log') : 'Log file not found';
    return response()->json([
        'log_exists' => file_exists('/tmp/db-config.log'),
        'log_content' => $logContent,
        'timestamp' => now()
    ]);
});

// Debug database connection with detailed error
Route::get('db-test', function () {
    try {
        // Test basic connection
        $pdo = DB::connection()->getPdo();
        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Database connected successfully!',
            'pdo_driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
            'server_info' => $pdo->getAttribute(PDO::ATTR_SERVER_INFO),
            'database_url' => env('DATABASE_URL') ? 'SET (' . strlen(env('DATABASE_URL')) . ' chars)' : 'NOT SET'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'database_url' => env('DATABASE_URL') ? 'SET (' . strlen(env('DATABASE_URL')) . ' chars)' : 'NOT SET',
            'current_db_config' => [
                'connection' => config('database.default'),
                'host' => config('database.connections.pgsql.host'),
                'port' => config('database.connections.pgsql.port'),
                'database' => config('database.connections.pgsql.database'),
                'username' => config('database.connections.pgsql.username')
            ],
            'fix_suggestion' => 'Check DATABASE_URL environment variable in Render'
        ], 200); // Return 200 so we can see the error
    }
});

// Test route for web routes (moved from web.php)
Route::get('web-test', function () {
    return response()->json([
        'message' => 'WebXemPhim API Backend',
        'status' => 'running',
        'version' => '1.0.1',
        'timestamp' => now(),
        'route_type' => 'api'
    ]);
});

// Test Google OAuth config
Route::get('test-google-config', function() {
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

// Debug movies table
Route::get('movies-debug', function () {
    try {
        $count = DB::table('movies')->count();
        $sample = DB::table('movies')->limit(3)->get();
        return response()->json([
            'movies_count' => $count,
            'sample_movies' => $sample
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'movies_table_exists' => false
        ], 500);
    }
});

// Public routes
Route::apiResource('movies', MovieController::class);
Route::apiResource('episodes', EpisodeController::class)->middleware('auth.api');
Route::get('movies/{movie}/related', [MovieController::class, 'related']);
Route::get('movies/{movie}/episodes', [MovieController::class, 'episodes'])->middleware('auth.api');
Route::get('search', [MovieController::class, 'search']);
Route::post('resend-verification', [AuthController::class, 'resendVerification']);
