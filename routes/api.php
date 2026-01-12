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

// Debug route for OAuth
Route::get('debug/oauth', function () {
    try {
        // Test if Socialite can be instantiated
        $driver = Socialite::driver('google');
        return response()->json(['status' => 'Socialite driver created successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    }
});
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
// Temporary debug route to return last lines of laravel.log. REMOVE after use.
Route::get('debug-last-log/{secret}', function ($secret) {
    if ($secret !== env('LOG_DEBUG_SECRET')) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    $path = storage_path('logs/laravel.log');
    if (!file_exists($path)) {
        return response()->json(['error' => 'Log not found'], 404);
    }
    $lines = array_slice(file($path), -500);
    return response()->json(['tail' => implode('', $lines)]);
});

// Temporary maintenance route: clear config cache and remove bootstrap cache files.
// REMOVE after use.
Route::post('debug-clear-config/{secret}', function ($secret) {
    if ($secret !== env('LOG_DEBUG_SECRET')) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    try {
        // Clear Laravel config cache and remove any cached files
        \Artisan::call('config:clear');
        @unlink(base_path('bootstrap/cache/config.php'));
        foreach (glob(base_path('bootstrap/cache/config-*.php')) as $f) {
            @unlink($f);
        }
        foreach (glob(base_path('bootstrap/cache/routes-*.php')) as $f) {
            @unlink($f);
        }
        foreach (glob(storage_path('logs/*.log')) as $f) {
            // do not delete logs; keep them
        }
        return response()->json(['status' => 'ok', 'message' => 'Config cleared and cache files removed.']);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Temporary debug route to list cache files (storage/framework/cache/data)
Route::get('list-cache-files/{secret}', function ($secret) {
    if ($secret !== env('LOG_DEBUG_SECRET')) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    $dir = storage_path('framework/cache/data');
    if (!is_dir($dir)) {
        return response()->json(['error' => 'Cache dir not found', 'path' => $dir], 404);
    }
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile()) {
            $files[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }
    return response()->json(['files' => $files]);
});

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
// Temporary debug route to return last lines of laravel.log. REMOVE after use.
Route::get('debug-last-log/{secret}', function ($secret) {
    if ($secret !== env('LOG_DEBUG_SECRET')) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    $path = storage_path('logs/laravel.log');
    if (!file_exists($path)) {
        return response()->json(['error' => 'Log not found'], 404);
    }
    $lines = array_slice(file($path), -500);
    return response()->json(['tail' => implode('', $lines)]);
});

// Temporary maintenance route: clear config cache and remove bootstrap cache files.
// REMOVE after use.
Route::post('debug-clear-config/{secret}', function ($secret) {
    if ($secret !== env('LOG_DEBUG_SECRET')) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    try {
        // Clear Laravel config cache and remove any cached files
        \Artisan::call('config:clear');
        @unlink(base_path('bootstrap/cache/config.php'));
        foreach (glob(base_path('bootstrap/cache/config-*.php')) as $f) {
            @unlink($f);
        }
        foreach (glob(base_path('bootstrap/cache/routes-*.php')) as $f) {
            @unlink($f);
        }
        foreach (glob(storage_path('logs/*.log')) as $f) {
            // do not delete logs; keep them
        }
        return response()->json(['status' => 'ok', 'message' => 'Config cleared and cache files removed.']);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Temporary debug route to list cache files (storage/framework/cache/data)
Route::get('list-cache-files/{secret}', function ($secret) {
    if ($secret !== env('LOG_DEBUG_SECRET')) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    $dir = storage_path('framework/cache/data');
    if (!is_dir($dir)) {
        return response()->json(['error' => 'Cache dir not found', 'path' => $dir], 404);
    }
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile()) {
            $files[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }
    return response()->json(['files' => $files]);
});
