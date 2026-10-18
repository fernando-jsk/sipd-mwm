<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../laravel-app/storage/framework/maintenance.php')) {
    require $maintenance;
} elseif (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader & Bootstrap Laravel...
if (file_exists(__DIR__.'/../laravel-app/vendor/autoload.php')) {
    require __DIR__.'/../laravel-app/vendor/autoload.php';
    /** @var Application $app */
    $app = require_once __DIR__.'/../laravel-app/bootstrap/app.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';
}

$app->handleRequest(Request::capture());

