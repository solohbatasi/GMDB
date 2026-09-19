<?php
require_once 'inc/store_catalog.php';

$apiBaseUrl = gdmb_store_api_base_url();
$apiUrl = $apiBaseUrl . '/books?limit=3';
$httpBody = gdmb_store_http_get($apiUrl);
$httpData = is_string($httpBody) ? json_decode($httpBody, true) : null;
$internalBody = gdmb_store_internal_api_request('GET', $apiUrl);
$internalData = is_string($internalBody) ? json_decode($internalBody, true) : null;
$bridgeBooks = gdmb_store_books(['limit' => 3]);

header('Content-Type: text/plain; charset=UTF-8');

echo "GDMB Store Diagnostics\n";
echo "======================\n\n";
echo 'API base URL: ' . $apiBaseUrl . "\n";
echo 'API books URL: ' . $apiUrl . "\n\n";

echo "HTTP loopback\n";
echo "-------------\n";
echo 'Returned body: ' . (is_string($httpBody) && $httpBody !== '' ? 'yes' : 'no') . "\n";
echo 'JSON decoded: ' . (is_array($httpData) ? 'yes' : 'no') . "\n";
echo 'Book count: ' . (is_array($httpData['data'] ?? null) ? count($httpData['data']) : 0) . "\n";

if (is_array($httpData['data'][0] ?? null)) {
    echo 'First HTTP book: ' . ($httpData['data'][0]['title'] ?? '-') . "\n";
    echo 'First HTTP availability: ' . ($httpData['data'][0]['availability'] ?? '-') . "\n";
    echo 'First HTTP price: ' . ($httpData['data'][0]['price'] ?? '-') . "\n";
}

echo "\nInternal Laravel dispatch\n";
echo "-------------------------\n";
echo 'Returned body: ' . (is_string($internalBody) && $internalBody !== '' ? 'yes' : 'no') . "\n";
echo 'JSON decoded: ' . (is_array($internalData) ? 'yes' : 'no') . "\n";
echo 'Book count: ' . (is_array($internalData['data'] ?? null) ? count($internalData['data']) : 0) . "\n";

if (is_array($internalData['data'][0] ?? null)) {
    echo 'First internal book: ' . ($internalData['data'][0]['title'] ?? '-') . "\n";
    echo 'First internal availability: ' . ($internalData['data'][0]['availability'] ?? '-') . "\n";
    echo 'First internal price: ' . ($internalData['data'][0]['price'] ?? '-') . "\n";
}

echo "\nLegacy bridge result\n";
echo "--------------------\n";
echo 'Book count: ' . count($bridgeBooks) . "\n";

if (isset($bridgeBooks[0])) {
    echo 'First bridge book: ' . ($bridgeBooks[0]['title'] ?? '-') . "\n";
    echo 'First bridge source: ' . ($bridgeBooks[0]['source'] ?? '-') . "\n";
    echo 'First bridge availability: ' . ($bridgeBooks[0]['availability'] ?? '-') . "\n";
    echo 'First bridge price: ' . ($bridgeBooks[0]['price'] ?? '-') . "\n";
}

echo "\nNotes\n";
echo "-----\n";
echo "If bridge source is legacy, the public site is not receiving Laravel API data.\n";
echo "If source is api but availability is untracked, check the book inventory row and track_stock value.\n";
echo "Add to Cart requires source api, available true, and price greater than 0.\n";
