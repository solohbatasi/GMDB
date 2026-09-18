<?php
require_once 'inc/cart.php';

$quote = gdmb_cart_display_quote();
$cartError = $_SESSION['gdmb_cart_error'] ?? null;
unset($_SESSION['gdmb_cart_error']);
?>
<section class="books-page">
    <div class="container">
        <?php include_once 'inc/breadcrumbs.php'; ?>
        <div class="books-page-header"><div><span class="books-eyebrow">Cart</span><h1>Your Book Cart</h1><p>Prices and availability are refreshed from the bookstore before checkout.</p></div><a href="./?p=books" class="btn btn-primary">Continue Shopping</a></div>
        <?php if ($cartError): ?>
            <p style="color:#b00020;"><?php echo gdmb_e($cartError); ?></p>
        <?php endif; ?>
        <?php if (empty($quote['items'])): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <?php if (empty($quote['quote_available'])): ?>
                <p style="color:#b00020;">Live checkout pricing is temporarily unavailable, but your cart item is saved below.</p>
            <?php endif; ?>
            <div class="books-grid">
                <?php foreach ($quote['items'] as $item): ?>
                    <article class="book-card">
                        <a class="book-card-cover" href="./?p=book&amp;slug=<?php echo rawurlencode($item['slug']); ?>"><img src="<?php echo gdmb_e($item['cover_url'] ?? ''); ?>" loading="lazy" alt="<?php echo gdmb_e($item['title'] ?? $item['slug']); ?>"></a>
                        <div class="book-card-body"><div class="book-meta"><span><?php echo gdmb_e($item['availability'] ?? ''); ?></span><span><?php echo gdmb_e($quote['currency'] ?? 'KES'); ?></span></div><h2><?php echo gdmb_e($item['title'] ?? $item['slug']); ?></h2><p>Unit: <?php echo gdmb_e(gdmb_format_price($item['unit_price'] ?? null, $quote['currency'] ?? 'KES')); ?></p><p>Line total: <?php echo gdmb_e(gdmb_format_price($item['line_total'] ?? null, $quote['currency'] ?? 'KES')); ?></p><?php if (! empty($item['message'])): ?><p><?php echo gdmb_e($item['message']); ?></p><?php endif; ?></div>
                        <div class="book-card-actions">
                            <form method="post" class="cart-quantity-form" style="display:flex; gap:8px; align-items:center;"><input type="hidden" name="action" value="update"><input type="hidden" name="slug" value="<?php echo gdmb_e($item['slug']); ?>"><input type="number" name="quantity" min="0" max="99" value="<?php echo (int) $item['quantity']; ?>" class="form-control cart-quantity-input" style="width:80px;" aria-label="Quantity for <?php echo gdmb_e($item['title'] ?? $item['slug']); ?>"><button class="book-btn book-btn-outline cart-update-button">Update</button></form>
                            <form method="post"><input type="hidden" name="action" value="remove"><input type="hidden" name="slug" value="<?php echo gdmb_e($item['slug']); ?>"><button class="book-btn book-btn-outline">Remove</button></form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:25px; text-align:right;">
                <?php if (! empty($quote['quote_available'])): ?>
                    <h3>Subtotal: <?php echo gdmb_e(gdmb_format_price($quote['subtotal'], $quote['currency'])); ?></h3>
                    <a href="./?p=checkout" class="book-btn book-btn-solid">Proceed to Checkout</a>
                <?php else: ?>
                    <a href="./?p=books" class="book-btn book-btn-outline">Continue Shopping</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cart-quantity-form').forEach(function (form) {
        var input = form.querySelector('.cart-quantity-input');
        var button = form.querySelector('.cart-update-button');
        var timer;

        if (!input) {
            return;
        }

        if (button) {
            button.style.display = 'none';
        }

        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                form.submit();
            }, 450);
        });

        input.addEventListener('change', function () {
            window.clearTimeout(timer);
            form.submit();
        });
    });
});
</script>
