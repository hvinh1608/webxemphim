<?php

echo "=== LARAVEL DEBUG ===\n\n";

// Check PHP
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current File: " . __FILE__ . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

// Check if Laravel bootstrap exists
$bootstrapPath = __DIR__ . '/../bootstrap/app.php';
echo "Bootstrap path: $bootstrapPath\n";
echo "Bootstrap exists: " . (file_exists($bootstrapPath) ? "YES" : "NO") . "\n\n";

if (file_exists($bootstrapPath)) {
    echo "=== TRYING TO LOAD LARAVEL ===\n";

    try {
        $app = require $bootstrapPath;
        echo "✅ Laravel app loaded successfully!\n";
        echo "Laravel version: " . app()->version() . "\n";

        // Check environment
        echo "APP_ENV: " . env('APP_ENV') . "\n";
        echo "APP_DEBUG: " . env('APP_DEBUG') . "\n";
        echo "APP_KEY: " . (env('APP_KEY') ? "SET (" . strlen(env('APP_KEY')) . " chars)" : "NOT SET") . "\n";

        // Check database
        try {
            $dbConfig = config('database');
            echo "DB_CONNECTION: " . env('DB_CONNECTION') . "\n";
            echo "DB_HOST: " . env('DB_HOST') . "\n";
            echo "DB_DATABASE: " . env('DB_DATABASE') . "\n";
        } catch (Exception $e) {
            echo "Database config error: " . $e->getMessage() . "\n";
        }

        // Check routes
        try {
            $routes = app('router')->getRoutes();
            echo "Routes loaded: " . count($routes) . " routes\n";
        } catch (Exception $e) {
            echo "Routes error: " . $e->getMessage() . "\n";
        }

    } catch (Exception $e) {
        echo "❌ Laravel load error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    }
} else {
    echo "❌ Bootstrap file not found!\n";
}

echo "\n=== DEBUG COMPLETE ===";
