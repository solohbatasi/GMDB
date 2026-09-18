<?php
require_once 'inc/store_catalog.php';

$orderNumber = $_GET['order'] ?? '';
$token = $_GET['token'] ?? '';
$response = ($orderNumber && $token) ? gdmb_store_api_get('orders/' . rawurlencode($orderNumber), ['token' => $token]) : null;
$order = is_array($response['data'] ?? null) ? $response['data'] : null;
?>
<section class="books-page"><div class="container"><?php include_once 'inc/breadcrumbs.php'; ?>
<?php if (! $order): ?><?php include '404.html'; ?><?php else: ?>
<div class="books-page-header"><div><span class="books-eyebrow">Awaiting Payment</span><h1>Thank you.</h1><p>Your order has been created. Payment has not been received yet.</p></div><a href="./?p=books" class="btn btn-primary">Continue Shopping</a></div>
<h2>Order <?php echo gdmb_e($order['order_number']); ?></h2><p>Payment: Awaiting Payment</p><p>Your books are reserved until <?php echo gdmb_e($order['reservation_expires_at']); ?> while payment is completed.</p>
<?php foreach ($order['items'] as $item): ?><p><?php echo gdmb_e($item['title']); ?> × <?php echo (int) $item['quantity']; ?> - <?php echo gdmb_e(gdmb_format_price($item['line_total'], $order['currency'])); ?></p><?php endforeach; ?>
<h3>Total: <?php echo gdmb_e(gdmb_format_price($order['total'], $order['currency'])); ?></h3>
<?php endif; ?></div></section>
