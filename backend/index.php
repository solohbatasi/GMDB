<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

$routePrefix = trim((string) ($_ENV['APP_ROUTE_PREFIX'] ?? getenv('APP_ROUTE_PREFIX') ?: basename(__DIR__)), '/');

if ($routePrefix !== '' && ! getenv('APP_ROUTE_PREFIX')) {
    $_ENV['APP_ROUTE_PREFIX'] = $routePrefix;
    $_SERVER['APP_ROUTE_PREFIX'] = $routePrefix;
    putenv('APP_ROUTE_PREFIX='.$routePrefix);
}

if ($routePrefix !== '' && isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/'.$routePrefix)) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
}

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
