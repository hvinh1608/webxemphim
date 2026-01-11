<?php

echo "=== SIMPLE DEBUG ===\n\n";

// Check basic PHP
echo "PHP OK\n";

// Check if autoload exists
$autoload = __DIR__ . '/../vendor/autoload.php';
echo "Autoload exists: " . (file_exists($autoload) ? "YES" : "NO") . "\n";

if (file_exists($autoload)) {
    echo "Loading autoload...\n";
    require $autoload;
    echo "Autoload loaded\n";
}

// Check if Laravel app exists
$appPath = __DIR__ . '/../bootstrap/app.php';
echo "App path exists: " . (file_exists($appPath) ? "YES" : "NO") . "\n";

if (file_exists($appPath)) {
    echo "Loading Laravel app...\n";
    try {
        $app = require $appPath;
        echo "Laravel app loaded successfully!\n";
        echo "Laravel version: " . $app->version() . "\n";
    } catch (Exception $e) {
        echo "Laravel app ERROR: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
    }
}

echo "\n=== END DEBUG ===";
