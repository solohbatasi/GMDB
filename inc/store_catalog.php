<?php

require_once __DIR__ . '/store_config.php';

function gdmb_e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function gdmb_format_price(mixed $price, ?string $currency = 'KES'): string
{
    if ($price === null || $price === '' || ! is_numeric($price) || (float) $price <= 0) {
        return '';
    }

    return trim(($currency ?: 'KES') . ' ' . number_format((float) $price, 0));
}

function gdmb_legacy_books(): array
{
    $books = [];
    require __DIR__ . '/books_data.php';

    return array_map('gdmb_normalize_legacy_book', $books);
}

function gdmb_normalize_legacy_book(array $book): array
{
    return [
        'title' => $book['title'] ?? '',
        'slug' => $book['slug'] ?? '',
        'author' => 'Duke Fitz-Theodore Randolph',
        'cover' => $book['cover'] ?? '',
        'category' => $book['tag'] ?? '',
        'category_slug' => isset($book['tag']) ? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $book['tag'])) : '',
        'summary' => $book['summary'] ?? '',
        'description' => $book['summary'] ?? '',
        'price' => null,
        'compare_price' => null,
        'currency' => 'KES',
        'featured' => true,
        'availability' => 'untracked',
        'available' => true,
        'purchase_url' => $book['purchase_url'] ?? null,
        'published_at' => $book['date'] ?? '',
        'seo_title' => $book['title'] ?? '',
        'seo_description' => $book['summary'] ?? '',
        'source' => 'legacy',
    ];
}

function gdmb_normalize_api_book(array $book): array
{
    $category = is_array($book['category'] ?? null) ? $book['category'] : [];

    return [
        'title' => $book['title'] ?? '',
        'slug' => $book['slug'] ?? '',
        'author' => $book['author'] ?? '',
        'cover' => $book['cover_url'] ?? '',
        'category' => $category['name'] ?? '',
        'category_slug' => $category['slug'] ?? '',
        'summary' => $book['short_description'] ?? '',
        'description' => $book['description'] ?? ($book['short_description'] ?? ''),
        'price' => $book['price'] ?? null,
        'compare_price' => $book['compare_price'] ?? null,
        'currency' => $book['currency'] ?? 'KES',
        'featured' => (bool) ($book['featured'] ?? false),
        'availability' => $book['availability'] ?? 'untracked',
        'available' => (bool) ($book['available'] ?? true),
        'purchase_url' => $book['external_purchase_url'] ?? null,
        'published_at' => $book['published_at'] ?? '',
        'seo_title' => $book['seo_title'] ?? ($book['title'] ?? ''),
        'seo_description' => $book['seo_description'] ?? ($book['short_description'] ?? ''),
        'source' => 'api',
    ];
}

function gdmb_store_books(array $params = []): array
{
    $data = gdmb_store_api_get('books', $params);

    if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
        return array_map('gdmb_normalize_api_book', $data['data']);
    }

    return gdmb_filter_legacy_books(gdmb_legacy_books(), $params);
}

function gdmb_featured_books(int $limit = 12): array
{
    $books = gdmb_store_books(['featured' => 1, 'limit' => $limit]);

    return $books ?: array_slice(gdmb_legacy_books(), 0, $limit);
}

function gdmb_store_book_by_slug(string $slug): ?array
{
    $slug = trim($slug);

    if ($slug === '' || ! preg_match('/\A[A-Za-z0-9_-]+\z/', $slug)) {
        return null;
    }

    $data = gdmb_store_api_get('books/' . rawurlencode($slug));

    if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
        return gdmb_normalize_api_book($data['data']);
    }

    foreach (gdmb_legacy_books() as $book) {
        if (($book['slug'] ?? '') === $slug) {
            return $book;
        }
    }

    return null;
}

function gdmb_store_api_get(string $path, array $params = []): ?array
{
    $url = gdmb_store_api_base_url() . '/' . ltrim($path, '/');
    $params = gdmb_clean_store_params($params);

    if ($params) {
        $url .= '?' . http_build_query($params);
    }

    $client = $GLOBALS['gdmb_store_http_client'] ?? null;

    if (is_callable($client)) {
        $body = $client($url);
    } else {
        $body = gdmb_store_http_get($url);
    }

    if (! is_string($body) || $body === '') {
        return null;
    }

    $decoded = json_decode($body, true);

    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
}

function gdmb_store_api_post(string $path, array $payload): ?array
{
    $url = gdmb_store_api_base_url() . '/' . ltrim($path, '/');
    $body = json_encode($payload);

    if (! is_string($body)) {
        return null;
    }

    $client = $GLOBALS['gdmb_store_http_post_client'] ?? null;

    if (is_callable($client)) {
        $response = $client($url, $body);
    } elseif (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_FAILONERROR => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $response = $status >= 200 && $status < 300 && is_string($response) ? $response : null;
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 4,
                'header' => "Accept: application/json\r\nContent-Type: application/json\r\n",
                'content' => $body,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        $response = preg_match('/\s2\d\d\s/', $statusLine) && is_string($response) ? $response : null;
    }

    if (! is_string($response) || $response === '') {
        return null;
    }

    $decoded = json_decode($response, true);

    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
}

function gdmb_store_http_get(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_FAILONERROR => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return $status >= 200 && $status < 300 && is_string($body) ? $body : null;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 2,
            'header' => "Accept: application/json\r\n",
            'ignore_errors' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';

    return str_contains($statusLine, ' 200 ') && is_string($body) ? $body : null;
}

function gdmb_clean_store_params(array $params): array
{
    $allowed = ['category', 'featured', 'search', 'sort', 'limit', 'page', 'token'];
    $clean = [];

    foreach ($allowed as $key) {
        if (! array_key_exists($key, $params) || $params[$key] === '' || $params[$key] === null) {
            continue;
        }

        $clean[$key] = is_string($params[$key]) ? substr(strip_tags($params[$key]), 0, 100) : $params[$key];
    }

    if (isset($clean['limit'])) {
        $clean['limit'] = max(1, min((int) $clean['limit'], 50));
    }

    return $clean;
}

function gdmb_filter_legacy_books(array $books, array $params): array
{
    if (! empty($params['search'])) {
        $needle = strtolower((string) $params['search']);
        $books = array_filter($books, fn (array $book) => str_contains(strtolower($book['title'] . ' ' . $book['summary'] . ' ' . $book['author']), $needle));
    }

    if (! empty($params['category'])) {
        $category = strtolower((string) $params['category']);
        $books = array_filter($books, fn (array $book) => strtolower($book['category_slug']) === $category || strtolower($book['category']) === $category);
    }

    if (isset($params['limit'])) {
        $books = array_slice($books, 0, max(1, min((int) $params['limit'], 50)));
    }

    return array_values($books);
}
