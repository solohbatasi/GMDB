<?php

function gdmb_public_routes(): array
{
    return [
        'home' => 'home.php',
        'contact' => 'contact.php',
        'core_values' => 'core_values.php',
        'history' => 'history.php',
        'mission' => 'mission.php',
        'team' => 'team.php',
        'login' => 'login.php',
        'register' => 'register.php',
        'forgot' => 'forgot.php',
        'coming_soon' => 'coming_soon.php',
        'single' => 'single.php',
        'books' => 'books/index.php',
        'books/12cs' => 'books/12cs.php',
        'books/body-over-brand' => 'books/body-over-brand.php',
        'books/book1' => 'books/book1.php',
        'books/calling-of-God' => 'books/calling-of-God.php',
        'books/kingdom-leadership' => 'books/kingdom-leadership.php',
        'books/look' => 'books/look.php',
        'books/marriage-made-in-heaven' => 'books/marriage-made-in-heaven.php',
        'books/miracle-of-genuine-confession' => 'books/miracle-of-genuine-confession.php',
        'books/pasis-model' => 'books/pasis-model.php',
        'books/prayer-that-works' => 'books/prayer-that-works.php',
        'books/when-heaven-touches-earth' => 'books/when-heaven-touches-earth.php',
        'blogs/baptism' => 'blogs/baptism.php',
        'blogs/faith' => 'blogs/faith.php',
        'blogs/shepherd' => 'blogs/shepherd.php',
        'events/empowerment-conference' => 'events/empowerment-conference.php',
        'videos' => 'videos/index.php',
    ];
}

function gdmb_normalize_route(?string $route): ?string
{
    $route = $route === null || $route === '' ? 'home' : $route;

    for ($i = 0; $i < 3; $i++) {
        $decoded = rawurldecode($route);

        if ($decoded === $route) {
            break;
        }

        $route = $decoded;
    }

    $route = trim(str_replace('\\', '/', $route), "/ \t\n\r\0\x0B");

    if ($route === '') {
        return 'home';
    }

    if (str_contains($route, '..') || ! preg_match('/\A[A-Za-z0-9_\/-]+\z/', $route)) {
        return null;
    }

    return $route;
}

function gdmb_resolve_public_route(?string $route, ?string $root = null): ?string
{
    $route = gdmb_normalize_route($route);

    if ($route === null) {
        return null;
    }

    $routes = gdmb_public_routes();

    if (! array_key_exists($route, $routes)) {
        return null;
    }

    $root = $root ?: dirname(__DIR__);
    $rootPath = realpath($root);
    $target = realpath($root . DIRECTORY_SEPARATOR . $routes[$route]);

    if ($rootPath === false || $target === false) {
        return null;
    }

    $rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (! str_starts_with($target, $rootPath)) {
        return null;
    }

    return $target;
}
