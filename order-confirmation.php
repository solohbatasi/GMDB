<?php
require_once 'inc/store_catalog.php';

$orderNumber = $_GET['order'] ?? '';
$token = $_GET['token'] ?? '';
$response = ($orderNumber && $token) ? gdmb_store_api_get('orders/' . rawurlencode($orderNumber), ['token' => $token]) : null;
$order = is_array($response['data'] ?? null) ? $response['data'] : null;
$orderLookupUrl = $order ? gdmb_store_api_base_url() . '/orders/' . rawurlencode($order['order_number']) . '?token=' . rawurlencode($token) : '';
$pickupLocation = is_array($order['fulfillment']['pickup_location'] ?? null) ? $order['fulfillment']['pickup_location'] : null;
$pickupMapUrl = $pickupLocation ? gdmb_pickup_map_url($pickupLocation) : '';
?>
<section class="books-page"><div class="container"><?php include_once 'inc/breadcrumbs.php'; ?>
<?php if (! $order): ?><?php include '404.html'; ?><?php else: ?>
<div class="books-page-header"><div><span class="books-eyebrow" id="payment-eyebrow"><?php echo $order['payment_status'] === 'paid' ? 'Payment Successful' : 'Awaiting M-Pesa'; ?></span><h1 id="payment-heading"><?php echo $order['payment_status'] === 'paid' ? 'Your order is confirmed.' : 'Check your phone'; ?></h1><p id="payment-copy"><?php echo $order['payment_status'] === 'paid' ? 'Your payment has been verified and your books are confirmed.' : 'Enter your M-Pesa PIN when prompted. Your books remain reserved while we confirm payment.'; ?></p></div><a href="./?p=books" class="btn btn-primary">Continue Shopping</a></div>

<div class="row">
    <div class="col-md-7">
        <h2>Order <?php echo gdmb_e($order['order_number']); ?></h2>
        <p><strong>Amount:</strong> <?php echo gdmb_e(gdmb_format_price($order['total'], $order['currency'])); ?></p>
        <p id="reservation-line"><strong>Your books are reserved for:</strong> <span id="reservation-countdown"><?php echo gdmb_e($order['reservation_expires_at']); ?></span></p>
        <p id="payment-status-line"><strong>Payment:</strong> <span id="payment-status"><?php echo gdmb_e($order['payment_status']); ?></span></p>
        <p id="provider-reference-line" style="<?php echo empty($order['provider_reference']) ? 'display:none;' : ''; ?>"><strong>Reference:</strong> <span id="provider-reference"><?php echo gdmb_e($order['provider_reference'] ?? ''); ?></span></p>

        <?php if ($pickupLocation): ?>
            <div class="book-card" style="padding:20px; margin:20px 0;">
                <h3>Pickup Point</h3>
                <p><strong><?php echo gdmb_e($pickupLocation['name'] ?? 'Pickup point'); ?></strong></p>
                <p><?php echo gdmb_e(trim(($pickupLocation['address'] ?? '') . ', ' . ($pickupLocation['city'] ?? '') . ', ' . ($pickupLocation['county'] ?? ''), ', ')); ?></p>
                <?php if (! empty($pickupLocation['instructions'])): ?><p><?php echo gdmb_e($pickupLocation['instructions']); ?></p><?php endif; ?>
                <a class="book-btn" href="<?php echo gdmb_e($pickupMapUrl); ?>" target="_blank" rel="noopener">View Map</a>
            </div>
        <?php endif; ?>

        <?php if ($order['payment_status'] !== 'paid' && ! empty($order['can_retry_payment'])): ?>
            <div id="payment-panel" class="book-card" style="padding:20px; margin:20px 0;">
                <h3>M-Pesa Payment</h3>
                <p id="payment-message">We are checking the payment status. If the prompt failed or was cancelled, return to checkout to retry this same order.</p>
                <a class="book-btn book-btn-solid" href="./?p=checkout">Return to Checkout</a>
            </div>
        <?php elseif ($order['payment_status'] !== 'paid'): ?>
            <div class="book-card" style="padding:20px; margin:20px 0;">
                <h3>Payment period expired</h3>
                <p>The stock reservation for this order has expired. Please return to the bookstore and create a new order.</p>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-5">
        <h3>Order Summary</h3>
        <?php foreach ($order['items'] as $item): ?><p><?php echo gdmb_e($item['title']); ?> x <?php echo (int) $item['quantity']; ?> - <?php echo gdmb_e(gdmb_format_price($item['line_total'], $order['currency'])); ?></p><?php endforeach; ?>
        <hr>
        <h3>Total: <?php echo gdmb_e(gdmb_format_price($order['total'], $order['currency'])); ?></h3>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var lookupUrl = <?php echo json_encode($orderLookupUrl); ?>;
    var token = <?php echo json_encode($token); ?>;
    var message = document.getElementById('payment-message');
    var statusText = document.getElementById('payment-status');
    var heading = document.getElementById('payment-heading');
    var copy = document.getElementById('payment-copy');
    var eyebrow = document.getElementById('payment-eyebrow');
    var providerReference = document.getElementById('provider-reference');
    var providerReferenceLine = document.getElementById('provider-reference-line');
    var paymentPanel = document.getElementById('payment-panel');
    var pollsRemaining = 100;
    var pollTimer = null;
    var cartCleared = false;

    function clearPaidCart() {
        if (cartCleared) return;
        cartCleared = true;
        var body = new URLSearchParams({ action: 'complete_checkout', order: <?php echo json_encode($orderNumber); ?>, token: token });
        fetch('./?p=checkout', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).catch(function () {});
    }

    function applyOrder(order) {
        statusText.textContent = order.payment_status;

        if (order.payment_status === 'paid') {
            eyebrow.textContent = 'Payment Successful';
            heading.textContent = 'Your order is confirmed.';
            copy.textContent = 'Your payment has been verified and your books are confirmed.';
            if (providerReference && order.provider_reference) {
                providerReference.textContent = order.provider_reference;
                providerReferenceLine.style.display = '';
            }
            if (paymentPanel) {
                paymentPanel.style.display = 'none';
            }
            clearPaidCart();
            window.clearTimeout(pollTimer);
        } else if (!order.can_retry_payment) {
            if (paymentPanel) {
                paymentPanel.style.display = 'none';
            }
        }
    }

    function pollOrder() {
        if (!lookupUrl || pollsRemaining <= 0) {
            return;
        }

        pollsRemaining--;
        fetch(lookupUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload && payload.data) {
                    applyOrder(payload.data);
                    if (payload.data.payment_status !== 'paid' && payload.data.can_retry_payment && pollsRemaining > 0) {
                        pollTimer = window.setTimeout(pollOrder, 4000);
                    }
                }
            })
            .catch(function () {
                if (pollsRemaining > 0) {
                    pollTimer = window.setTimeout(pollOrder, 5000);
                }
            });
    }

    applyOrder(<?php echo json_encode($order, JSON_UNESCAPED_SLASHES); ?>);
    if (<?php echo json_encode($order['payment_status'] !== 'paid'); ?>) pollOrder();
});
</script>
<?php endif; ?></div></section>
