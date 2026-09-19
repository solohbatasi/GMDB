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

function gdmb_cart_handle_request(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $action = $_POST['action'] ?? '';
    $slug = (string) ($_POST['slug'] ?? '');
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($action === 'add' || $action === 'buy_now') {
        if (! gdmb_cart_add($slug, $quantity)) {
            $_SESSION['gdmb_cart_error'] = 'The selected book could not be added to the cart.';
        }
        header('Location: ./' . ($action === 'buy_now' ? '?p=checkout' : '?p=cart'));
        exit;
    }

    if ($action === 'update') {
        gdmb_cart_update($slug, $quantity);
    } elseif ($action === 'remove') {
        gdmb_cart_remove($slug);
    }

    header('Location: ./?p=cart');
    exit;
}

function gdmb_checkout_handle_request(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $quote = gdmb_cart_quote();
    $GLOBALS['gdmb_checkout_quote'] = $quote;

    if (! $quote && gdmb_cart_count() > 0) {
        $GLOBALS['gdmb_checkout_error'] = 'Live checkout pricing is temporarily unavailable. Please try again shortly.';

        return;
    }

    if (! $quote || empty($quote['valid'])) {
        $GLOBALS['gdmb_checkout_error'] = 'Your cart could not be checked out. Please review the cart and try again.';

        return;
    }

    gdmb_session_start();
    $_SESSION['gdmb_checkout_token'] ??= bin2hex(random_bytes(24));

    $response = gdmb_store_api_post('checkout', [
        'checkout_token' => $_SESSION['gdmb_checkout_token'],
        'items' => gdmb_cart_items(),
        'customer' => [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
        ],
        'fulfillment' => [
            'method' => 'pickup',
            'address' => null,
            'city' => null,
            'county' => null,
            'pickup_location_id' => $_POST['pickup_location_id'] ?? null,
        ],
        'customer_note' => $_POST['customer_note'] ?? null,
    ]);

    if (isset($response['data']['order_number'], $response['data']['public_token'])) {
        gdmb_cart_clear();
        unset($_SESSION['gdmb_checkout_token']);
        header('Location: ./?p=order-confirmation&order=' . rawurlencode($response['data']['order_number']) . '&token=' . rawurlencode($response['data']['public_token']));
        exit;
    }

    $GLOBALS['gdmb_checkout_error'] = $response['message'] ?? 'Checkout could not be completed. Please review your details and try again.';
}

function gdmb_cart_quote(): ?array
{
    $response = gdmb_store_api_post('cart/quote', ['items' => gdmb_cart_items()]);

    return is_array($response) && isset($response['data']) ? $response['data'] : null;
}

function gdmb_cart_display_quote(): array
{
    $quote = gdmb_cart_quote();

    if (is_array($quote)) {
        $quote['quote_available'] = true;

        return $quote;
    }

    $items = [];

    foreach (gdmb_cart_items() as $cartItem) {
        $book = gdmb_store_book_by_slug((string) $cartItem['slug']);

        $items[] = [
            'slug' => $cartItem['slug'],
            'title' => $book['title'] ?? $cartItem['slug'],
            'author' => $book['author'] ?? '',
            'cover_url' => $book['cover'] ?? '',
            'unit_price' => $book['price'] ?? null,
            'quantity' => (int) $cartItem['quantity'],
            'line_total' => is_numeric($book['price'] ?? null) ? (float) $book['price'] * (int) $cartItem['quantity'] : null,
            'availability' => $book['availability'] ?? 'unknown',
            'message' => 'Live price and stock will refresh when the bookstore service is available.',
        ];
    }

    return [
        'items' => $items,
        'subtotal' => null,
        'shipping_total' => null,
        'total' => null,
        'currency' => 'KES',
        'valid' => false,
        'quote_available' => false,
    ];
}
