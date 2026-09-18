<?php
require_once 'inc/cart.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $slug = (string) ($_POST['slug'] ?? '');
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($action === 'add' || $action === 'buy_now') {
        gdmb_cart_add($slug, $quantity);
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

$quote = gdmb_cart_quote();
?>
<section class="books-page">
    <div class="container">
        <?php include_once 'inc/breadcrumbs.php'; ?>
        <div class="books-page-header"><div><span class="books-eyebrow">Cart</span><h1>Your Book Cart</h1><p>Prices and availability are refreshed from the bookstore before checkout.</p></div><a href="./?p=books" class="btn btn-primary">Continue Shopping</a></div>
        <?php if (! $quote || empty($quote['items'])): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <div class="books-grid">
                <?php foreach ($quote['items'] as $item): ?>
                    <article class="book-card">
                        <a class="book-card-cover" href="./?p=book&amp;slug=<?php echo rawurlencode($item['slug']); ?>"><img src="<?php echo gdmb_e($item['cover_url'] ?? ''); ?>" loading="lazy" alt="<?php echo gdmb_e($item['title'] ?? $item['slug']); ?>"></a>
                        <div class="book-card-body"><div class="book-meta"><span><?php echo gdmb_e($item['availability'] ?? ''); ?></span><span><?php echo gdmb_e($quote['currency'] ?? 'KES'); ?></span></div><h2><?php echo gdmb_e($item['title'] ?? $item['slug']); ?></h2><p>Unit: <?php echo gdmb_e(gdmb_format_price($item['unit_price'] ?? null, $quote['currency'] ?? 'KES')); ?></p><p>Line total: <?php echo gdmb_e(gdmb_format_price($item['line_total'] ?? null, $quote['currency'] ?? 'KES')); ?></p><?php if (! empty($item['message'])): ?><p><?php echo gdmb_e($item['message']); ?></p><?php endif; ?></div>
                        <div class="book-card-actions">
                            <form method="post" style="display:flex; gap:8px; align-items:center;"><input type="hidden" name="action" value="update"><input type="hidden" name="slug" value="<?php echo gdmb_e($item['slug']); ?>"><input type="number" name="quantity" min="0" max="99" value="<?php echo (int) $item['quantity']; ?>" class="form-control" style="width:80px;"><button class="book-btn book-btn-outline">Update</button></form>
                            <form method="post"><input type="hidden" name="action" value="remove"><input type="hidden" name="slug" value="<?php echo gdmb_e($item['slug']); ?>"><button class="book-btn book-btn-outline">Remove</button></form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:25px; text-align:right;"><h3>Subtotal: <?php echo gdmb_e(gdmb_format_price($quote['subtotal'], $quote['currency'])); ?></h3><a href="./?p=checkout" class="book-btn book-btn-solid">Proceed to Checkout</a></div>
        <?php endif; ?>
    </div>
</section>
