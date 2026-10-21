<?php

use Illuminate\Http\Request;

// Let PHP's built-in local server return real files from public/ before Laravel handles a route.
if (PHP_SAPI === 'cli-server') {
    $requestPath = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
    $staticFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim($requestPath, '/\\'));

    if (
        $requestPath !== '/'
        && $staticFile !== false
        && str_starts_with($staticFile, __DIR__ . DIRECTORY_SEPARATOR)
        && is_file($staticFile)
    ) {
        return false;
    }
}

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
