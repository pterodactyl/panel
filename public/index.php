<?php

/**
 * Laravel - A PHP Framework For Web Artisans.
 *
 * @author   Taylor Otwell <taylor@laravel.com>
 */

// Set the start time for the script
define('LARAVEL_START', microtime(true));

// Check if the application is in maintenance/demo mode
if (file_exists(__DIR__ . '/../storage/framework/maintenance.php')) {
    require __DIR__ . '/../storage/framework/maintenance.php';
}

// Require the composer-generated class loader
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap the framework and prepare the application for use
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Handle the incoming request and send the response back to the browser
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());
$response->send();

// Terminate the request
$kernel->terminate($request, $response);
