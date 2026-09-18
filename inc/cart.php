<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/store_catalog.php';

function gdmb_cart_boot(): void
{
    gdmb_session_start();
    $_SESSION['gdmb_cart'] ??= [];
}

function gdmb_cart_add(string $slug, int $quantity = 1): bool
{
    gdmb_cart_boot();
    $slug = trim($slug);

    if (! preg_match('/\A[A-Za-z0-9_-]+\z/', $slug) || $quantity < 1) {
        return false;
    }

    $quantity = min($quantity, 99);
    $current = (int) ($_SESSION['gdmb_cart'][$slug]['quantity'] ?? 0);
    $_SESSION['gdmb_cart'][$slug] = ['slug' => $slug, 'quantity' => min(99, $current + $quantity)];

    return true;
}

function gdmb_cart_update(string $slug, int $quantity): bool
{
    gdmb_cart_boot();

    if (! preg_match('/\A[A-Za-z0-9_-]+\z/', $slug)) {
        return false;
    }

    if ($quantity <= 0) {
        unset($_SESSION['gdmb_cart'][$slug]);
        return true;
    }

    $_SESSION['gdmb_cart'][$slug] = ['slug' => $slug, 'quantity' => min(99, $quantity)];

    return true;
}

function gdmb_cart_remove(string $slug): void
{
    gdmb_cart_boot();
    unset($_SESSION['gdmb_cart'][$slug]);
}

function gdmb_cart_clear(): void
{
    gdmb_cart_boot();
    $_SESSION['gdmb_cart'] = [];
}

function gdmb_cart_items(): array
{
    gdmb_cart_boot();

    return array_values($_SESSION['gdmb_cart']);
}

function gdmb_cart_count(): int
{
    return array_sum(array_map(fn ($item) => (int) $item['quantity'], gdmb_cart_items()));
}

function gdmb_cart_quote(): ?array
{
    $response = gdmb_store_api_post('cart/quote', ['items' => gdmb_cart_items()]);

    return is_array($response) && isset($response['data']) ? $response['data'] : null;
}
