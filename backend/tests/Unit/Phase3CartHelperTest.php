<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class Phase3CartHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__, 3).'/inc/cart.php';
        $_SESSION = [];
    }

    public function test_cart_add_update_remove_and_count_work(): void
    {
        $this->assertTrue(gdmb_cart_add('book1', 2));
        $this->assertTrue(gdmb_cart_add('book1', 3));
        $this->assertSame(5, gdmb_cart_count());

        $this->assertTrue(gdmb_cart_update('book1', 1));
        $this->assertSame([['slug' => 'book1', 'quantity' => 1]], gdmb_cart_items());

        gdmb_cart_remove('book1');
        $this->assertSame(0, gdmb_cart_count());
    }

    public function test_cart_rejects_bad_slug_and_enforces_bounds(): void
    {
        $this->assertFalse(gdmb_cart_add('../bad', 1));
        $this->assertTrue(gdmb_cart_add('book1', 150));
        $this->assertSame(99, gdmb_cart_items()[0]['quantity']);

        gdmb_cart_update('book1', 0);
        $this->assertSame([], gdmb_cart_items());
    }

    public function test_cart_clear_only_when_called(): void
    {
        gdmb_cart_add('book1', 1);
        $this->assertSame(1, gdmb_cart_count());
        gdmb_cart_clear();
        $this->assertSame(0, gdmb_cart_count());
    }
}
