<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/store_catalog.php';

function gdmb_cart_boot(): void
{
    gdmb_session_start();
    if (! isset($_SESSION['gdmb_cart'])) {
        $_SESSION['gdmb_cart'] = [];
    }

    $normalized = [];
    foreach ($_SESSION['gdmb_cart'] as $item) {
        $slug = (string) ($item['slug'] ?? '');
        $priceOption = gdmb_cart_price_option((string) ($item['price_option'] ?? 'primary'));
        if (! preg_match('/\A[A-Za-z0-9_-]+\z/', $slug)) {
            continue;
        }

        $key = gdmb_cart_item_key($slug, $priceOption);
        $normalized[$key] = [
            'slug' => $slug,
            'price_option' => $priceOption,
            'quantity' => min(99, max(1, (int) ($item['quantity'] ?? 1))),
        ];
    }
    $_SESSION['gdmb_cart'] = $normalized;
}

function gdmb_cart_price_option(string $priceOption): string
{
    return $priceOption === 'secondary' ? 'secondary' : 'primary';
}

function gdmb_cart_item_key(string $slug, string $priceOption = 'primary'): string
{
    return $slug.'|'.gdmb_cart_price_option($priceOption);
}

function gdmb_cart_add(string $slug, int $quantity = 1, string $priceOption = 'primary'): bool
{
    gdmb_cart_boot();
    $slug = trim($slug);

    if (! preg_match('/\A[A-Za-z0-9_-]+\z/', $slug) || $quantity < 1) {
        return false;
    }

    $quantity = min($quantity, 99);
    $priceOption = gdmb_cart_price_option($priceOption);
    $key = gdmb_cart_item_key($slug, $priceOption);
    $current = (int) ($_SESSION['gdmb_cart'][$key]['quantity'] ?? 0);
    $_SESSION['gdmb_cart'][$key] = [
        'slug' => $slug,
        'price_option' => $priceOption,
        'quantity' => min(99, $current + $quantity),
    ];

    return true;
}

function gdmb_cart_update(string $slug, int $quantity, string $priceOption = 'primary'): bool
{
    gdmb_cart_boot();

    if (! preg_match('/\A[A-Za-z0-9_-]+\z/', $slug)) {
        return false;
    }

    $key = gdmb_cart_item_key($slug, $priceOption);

    if ($quantity <= 0) {
        unset($_SESSION['gdmb_cart'][$key]);
        return true;
    }

    $_SESSION['gdmb_cart'][$key] = [
        'slug' => $slug,
        'price_option' => gdmb_cart_price_option($priceOption),
        'quantity' => min(99, $quantity),
    ];

    return true;
}

function gdmb_cart_remove(string $slug, string $priceOption = 'primary'): void
{
    gdmb_cart_boot();
    unset($_SESSION['gdmb_cart'][gdmb_cart_item_key($slug, $priceOption)]);
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
    return array_sum(array_map(function ($item) {
        return (int) $item['quantity'];
    }, gdmb_cart_items()));
}

function gdmb_cart_handle_request(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $action = $_POST['action'] ?? '';
    $slug = (string) ($_POST['slug'] ?? '');
    $quantity = (int) ($_POST['quantity'] ?? 1);
    $priceOption = gdmb_cart_price_option((string) ($_POST['price_option'] ?? 'primary'));

    if ($action === 'add' || $action === 'buy_now') {
        if (! gdmb_cart_add($slug, $quantity, $priceOption)) {
            $_SESSION['gdmb_cart_error'] = 'The selected book could not be added to the cart.';
        }
        header('Location: ./' . ($action === 'buy_now' ? '?p=checkout' : '?p=cart'));
        exit;
    }

    if ($action === 'update') {
        gdmb_cart_update($slug, $quantity, $priceOption);
    } elseif ($action === 'remove') {
        gdmb_cart_remove($slug, $priceOption);
    }

    header('Location: ./?p=cart');
    exit;
}

function gdmb_checkout_handle_request(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    gdmb_session_start();

    if (($_POST['action'] ?? '') === 'complete_checkout') {
        $orderNumber = trim((string) ($_POST['order'] ?? ''));
        $token = trim((string) ($_POST['token'] ?? ''));
        $response = ($orderNumber !== '' && $token !== '')
            ? gdmb_store_api_get('orders/'.rawurlencode($orderNumber), ['token' => $token])
            : null;
        $paid = ($response['data']['payment_status'] ?? null) === 'paid';

        if ($paid) {
            gdmb_cart_clear();
            unset($_SESSION['gdmb_checkout_token']);
        }

        header('Content-Type: application/json');
        http_response_code($paid ? 200 : 409);
        echo json_encode(['completed' => $paid]);
        exit;
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

    if (! isset($_SESSION['gdmb_checkout_token'])) {
        $_SESSION['gdmb_checkout_token'] = bin2hex(random_bytes(24));
    }

    $response = gdmb_store_api_post('checkout', [
        'checkout_token' => $_SESSION['gdmb_checkout_token'],
        'items' => gdmb_cart_items(),
        'customer' => [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
        ],
        'payment' => [
            'phone' => $_POST['mpesa_phone'] ?? $_POST['phone'] ?? '',
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
        $priceOption = gdmb_cart_price_option((string) ($cartItem['price_option'] ?? 'primary'));
        $selectedPrice = $priceOption === 'secondary' && is_numeric($book['compare_price'] ?? null) && (float) $book['compare_price'] > 0
            ? $book['compare_price']
            : ($book['price'] ?? null);

        $items[] = [
            'slug' => $cartItem['slug'],
            'price_option' => $priceOption,
            'price_option_label' => $priceOption === 'secondary' ? 'Paperback' : 'Hardcover',
            'title' => $book['title'] ?? $cartItem['slug'],
            'author' => $book['author'] ?? '',
            'cover_url' => $book['cover'] ?? '',
            'unit_price' => $selectedPrice,
            'quantity' => (int) $cartItem['quantity'],
            'line_total' => is_numeric($selectedPrice) ? (float) $selectedPrice * (int) $cartItem['quantity'] : null,
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
