<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LegacyRouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__, 3).'/inc/router.php';
    }

    public function test_known_legacy_routes_resolve(): void
    {
        $this->assertStringEndsWith('books'.DIRECTORY_SEPARATOR.'index.php', gdmb_resolve_public_route('books', dirname(__DIR__, 3)));
        $this->assertStringEndsWith('books'.DIRECTORY_SEPARATOR.'book1.php', gdmb_resolve_public_route('books/book1', dirname(__DIR__, 3)));
    }

    public function test_unsafe_traversal_routes_are_rejected(): void
    {
        $root = dirname(__DIR__, 3);

        $this->assertNull(gdmb_resolve_public_route('../backend/.env', $root));
        $this->assertNull(gdmb_resolve_public_route('..%2Fbackend%2F.env', $root));
        $this->assertNull(gdmb_resolve_public_route('%252e%252e%252fbackend%252f.env', $root));
    }

    public function test_unknown_routes_are_rejected(): void
    {
        $this->assertNull(gdmb_resolve_public_route('not-a-real-page', dirname(__DIR__, 3)));
    }
}
