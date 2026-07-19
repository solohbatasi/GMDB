<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

$basePath = dirname(__DIR__);
$routePrefix = $_ENV['APP_ROUTE_PREFIX'] ?? getenv('APP_ROUTE_PREFIX') ?: null;

// Routing is configured before Laravel loads .env. Read only this routing
// value here so CLI commands and web requests agree without preloading the
// entire environment ahead of PHPUnit or Laravel's normal bootstrap.
if ($routePrefix === null && is_file($basePath.'/.env')) {
    foreach (file($basePath.'/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (preg_match('/^\s*APP_ROUTE_PREFIX\s*=\s*(.*)\s*$/', $line, $matches)) {
            $routePrefix = trim($matches[1], " \t\n\r\0\x0B\"'");
            break;
        }
    }
}

$routePrefix = trim((string) $routePrefix, '/');
$apiPrefix = trim(($routePrefix !== '' ? $routePrefix.'/' : '').'api', '/');

return Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: $apiPrefix,
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($apiPrefix): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is($apiPrefix.'/*') || $request->expectsJson(),
        );
    })->create();
