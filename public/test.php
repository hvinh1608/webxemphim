<?php
echo "PHP is working!\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Laravel base path: " . realpath(__DIR__ . '/../') . "\n";

// Check if Laravel is loaded
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "Composer autoload exists\n";
    require_once __DIR__ . '/../vendor/autoload.php';

    if (class_exists('Illuminate\Foundation\Application')) {
        echo "Laravel Application class exists\n";

        try {
            $app = require_once __DIR__ . '/../bootstrap/app.php';
            echo "Laravel app loaded successfully\n";
            echo "Laravel version: " . app()->version() . "\n";
            echo "APP_ENV: " . env('APP_ENV') . "\n";
            echo "APP_DEBUG: " . env('APP_DEBUG') . "\n";
        } catch (Exception $e) {
            echo "Laravel app error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Laravel Application class not found\n";
    }
} else {
    echo "Composer autoload not found\n";
}

echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
?>


echo "PHP is working!\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Laravel base path: " . realpath(__DIR__ . '/../') . "\n";

// Check if Laravel is loaded
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "Composer autoload exists\n";
    require_once __DIR__ . '/../vendor/autoload.php';

    if (class_exists('Illuminate\Foundation\Application')) {
        echo "Laravel Application class exists\n";

        try {
            $app = require_once __DIR__ . '/../bootstrap/app.php';
            echo "Laravel app loaded successfully\n";
            echo "Laravel version: " . app()->version() . "\n";
            echo "APP_ENV: " . env('APP_ENV') . "\n";
            echo "APP_DEBUG: " . env('APP_DEBUG') . "\n";
        } catch (Exception $e) {
            echo "Laravel app error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Laravel Application class not found\n";
    }
} else {
    echo "Composer autoload not found\n";
}

echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
?>


