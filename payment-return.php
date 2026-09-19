<?php
require_once 'inc/store_catalog.php';

$reference = trim((string) ($_GET['reference'] ?? $_GET['trxref'] ?? ''));
$response = $reference !== '' ? gdmb_store_api_post('payments/paystack/verify', ['reference' => $reference]) : null;
$payment = is_array($response['data'] ?? null) ? $response['data'] : null;
$status = $payment['status'] ?? null;
$verified = $status === 'paid';
$pickupLocation = is_array($payment['pickup_location'] ?? null) ? $payment['pickup_location'] : null;
$pickupMapUrl = $pickupLocation ? gdmb_pickup_map_url($pickupLocation) : '';
?>
<section class="books-page"><div class="container"><?php include_once 'inc/breadcrumbs.php'; ?>
<div class="books-page-header">
    <div>
        <span class="books-eyebrow"><?php echo $verified ? 'Payment Successful' : 'Payment Pending'; ?></span>
        <h1><?php echo $verified ? 'Your order is confirmed.' : 'We are checking your payment.'; ?></h1>
        <p><?php echo $verified ? 'Your payment has been verified and your books are confirmed.' : 'If you completed payment, please refresh this page in a moment.'; ?></p>
    </div>
    <a href="./?p=books" class="btn btn-primary">Continue Shopping</a>
</div>

<div class="book-card" style="padding:20px; margin:20px 0;">
    <?php if ($reference === ''): ?>
        <h3>Missing payment reference</h3>
        <p>Paystack did not return a payment reference. Please contact support with your order details.</p>
    <?php elseif (! $payment): ?>
        <h3>Payment could not be verified yet</h3>
        <p><?php echo gdmb_e($response['message'] ?? 'Please refresh this page in a moment.'); ?></p>
        <p><strong>Reference:</strong> <?php echo gdmb_e($reference); ?></p>
    <?php else: ?>
        <h3>Payment Status: <?php echo gdmb_e($status); ?></h3>
        <p><strong>Order:</strong> <?php echo gdmb_e($payment['order_number'] ?? '-'); ?></p>
        <p><strong>Reference:</strong> <?php echo gdmb_e($payment['provider_reference'] ?? $payment['reference'] ?? $reference); ?></p>
        <p><strong>Amount:</strong> <?php echo gdmb_e(gdmb_format_price($payment['amount'] ?? null, $payment['currency'] ?? 'KES')); ?></p>
        <?php if (! empty($payment['channel'])): ?><p><strong>Channel:</strong> <?php echo gdmb_e($payment['channel']); ?></p><?php endif; ?>
        <?php if ($pickupLocation): ?>
            <hr>
            <h3>Pickup Point</h3>
            <p><strong><?php echo gdmb_e($pickupLocation['name'] ?? 'Pickup point'); ?></strong></p>
            <p><?php echo gdmb_e(trim(($pickupLocation['address'] ?? '') . ', ' . ($pickupLocation['city'] ?? '') . ', ' . ($pickupLocation['county'] ?? ''), ', ')); ?></p>
            <?php if (! empty($pickupLocation['instructions'])): ?><p><?php echo gdmb_e($pickupLocation['instructions']); ?></p><?php endif; ?>
            <a class="book-btn" href="<?php echo gdmb_e($pickupMapUrl); ?>" target="_blank" rel="noopener">View Map</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div></section>
