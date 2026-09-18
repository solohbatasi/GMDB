<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StoreCatalogHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__, 3).'/inc/store_catalog.php';
        unset($GLOBALS['gdmb_store_http_client']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['gdmb_store_http_client']);

        parent::tearDown();
    }

    public function test_valid_laravel_response_is_normalized(): void
    {
        $GLOBALS['gdmb_store_http_client'] = fn () => json_encode([
            'data' => [[
                'title' => 'Dynamic Book',
                'slug' => 'dynamic-book',
                'author' => 'Author',
                'short_description' => 'Short',
                'description' => 'Long',
                'category' => ['name' => 'Faith', 'slug' => 'faith'],
                'price' => 1500,
                'currency' => 'KES',
                'cover_url' => '/storage/books/cover.jpg',
                'featured' => true,
                'availability' => 'in_stock',
                'available' => true,
                'external_purchase_url' => 'https://example.com',
            ]],
        ]);

        $books = gdmb_store_books();

        $this->assertSame('Dynamic Book', $books[0]['title']);
        $this->assertSame('Faith', $books[0]['category']);
        $this->assertSame('/storage/books/cover.jpg', $books[0]['cover']);
        $this->assertSame(1500, $books[0]['price']);
    }

    public function test_api_failure_falls_back_to_static_books(): void
    {
        $GLOBALS['gdmb_store_http_client'] = fn () => null;

        $books = gdmb_store_books();

        $this->assertNotEmpty($books);
        $this->assertSame('legacy', $books[0]['source']);
        $this->assertSame('A Theory of Lay Ministry Praxis', $books[0]['title']);
    }

    public function test_malformed_json_falls_back_safely(): void
    {
        $GLOBALS['gdmb_store_http_client'] = fn () => '{bad json';

        $this->assertSame('legacy', gdmb_store_books()[0]['source']);
    }

    public function test_featured_books_can_be_received_from_api(): void
    {
        $GLOBALS['gdmb_store_http_client'] = fn (string $url) => str_contains($url, 'featured=1') ? json_encode([
            'data' => [[
                'title' => 'Featured Dynamic',
                'slug' => 'featured-dynamic',
                'author' => 'Author',
                'short_description' => 'Short',
                'category' => null,
                'price' => null,
                'currency' => 'KES',
                'cover_url' => '/cover.jpg',
                'featured' => true,
                'availability' => 'untracked',
                'available' => true,
            ]],
        ]) : null;

        $this->assertSame('Featured Dynamic', gdmb_featured_books(3)[0]['title']);
    }

    public function test_book_lookup_by_slug_works_with_api_and_fallback(): void
    {
        $GLOBALS['gdmb_store_http_client'] = fn () => json_encode([
            'data' => [
                'title' => 'Single Dynamic',
                'slug' => 'single-dynamic',
                'author' => 'Author',
                'short_description' => 'Short',
                'category' => null,
                'price' => null,
                'currency' => 'KES',
                'cover_url' => '/cover.jpg',
                'featured' => false,
                'availability' => 'untracked',
                'available' => true,
            ],
        ]);

        $this->assertSame('Single Dynamic', gdmb_store_book_by_slug('single-dynamic')['title']);

        $GLOBALS['gdmb_store_http_client'] = fn () => null;

        $this->assertSame('book1', gdmb_store_book_by_slug('book1')['slug']);
    }

    public function test_escape_and_price_helpers_work(): void
    {
        $this->assertSame('&lt;script&gt;', gdmb_e('<script>'));
        $this->assertSame('KES 1,500', gdmb_format_price(1500, 'KES'));
        $this->assertSame('', gdmb_format_price(null, 'KES'));
    }
}
