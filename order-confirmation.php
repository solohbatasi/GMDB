<?php
require_once 'inc/store_catalog.php';

$orderNumber = $_GET['order'] ?? '';
$token = $_GET['token'] ?? '';
$response = ($orderNumber && $token) ? gdmb_store_api_get('orders/' . rawurlencode($orderNumber), ['token' => $token]) : null;
$order = is_array($response['data'] ?? null) ? $response['data'] : null;
$orderLookupUrl = $order ? gdmb_store_api_base_url() . '/orders/' . rawurlencode($order['order_number']) . '?token=' . rawurlencode($token) : '';
$paymentUrl = $order ? gdmb_store_api_base_url() . '/orders/' . rawurlencode($order['order_number']) . '/payments/payhero' : '';
?>
<section class="books-page"><div class="container"><?php include_once 'inc/breadcrumbs.php'; ?>
<?php if (! $order): ?><?php include '404.html'; ?><?php else: ?>
<div class="books-page-header"><div><span class="books-eyebrow" id="payment-eyebrow"><?php echo $order['payment_status'] === 'paid' ? 'Payment Successful' : 'Awaiting Payment'; ?></span><h1 id="payment-heading"><?php echo $order['payment_status'] === 'paid' ? 'Your order is confirmed.' : 'Complete Your Payment'; ?></h1><p id="payment-copy"><?php echo $order['payment_status'] === 'paid' ? 'Your payment has been verified and your books are confirmed.' : 'Your books are reserved while payment is completed.'; ?></p></div><a href="./?p=books" class="btn btn-primary">Continue Shopping</a></div>

<div class="row">
    <div class="col-md-7">
        <h2>Order <?php echo gdmb_e($order['order_number']); ?></h2>
        <p><strong>Amount:</strong> <?php echo gdmb_e(gdmb_format_price($order['total'], $order['currency'])); ?></p>
        <p id="reservation-line"><strong>Your books are reserved for:</strong> <span id="reservation-countdown"><?php echo gdmb_e($order['reservation_expires_at']); ?></span></p>
        <p id="payment-status-line"><strong>Payment:</strong> <span id="payment-status"><?php echo gdmb_e($order['payment_status']); ?></span></p>
        <p id="provider-reference-line" style="<?php echo empty($order['provider_reference']) ? 'display:none;' : ''; ?>"><strong>Reference:</strong> <span id="provider-reference"><?php echo gdmb_e($order['provider_reference'] ?? ''); ?></span></p>

        <?php if ($order['payment_status'] !== 'paid' && ! empty($order['can_retry_payment'])): ?>
            <div id="payment-panel" class="book-card" style="padding:20px; margin:20px 0;">
                <h3>Payment Method</h3>
                <p>M-Pesa / PayHero</p>
                <input id="payment-phone" class="form-control" value="<?php echo gdmb_e($order['customer']['phone'] ?? ''); ?>" placeholder="Payment phone e.g. 0712345678">
                <br>
                <button id="pay-now-button" class="book-btn book-btn-solid" type="button">Pay Now</button>
                <p id="payment-message" style="margin-top:12px;"></p>
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
    var paymentUrl = <?php echo json_encode($paymentUrl); ?>;
    var token = <?php echo json_encode($token); ?>;
    var button = document.getElementById('pay-now-button');
    var message = document.getElementById('payment-message');
    var statusText = document.getElementById('payment-status');
    var heading = document.getElementById('payment-heading');
    var copy = document.getElementById('payment-copy');
    var eyebrow = document.getElementById('payment-eyebrow');
    var providerReference = document.getElementById('provider-reference');
    var providerReferenceLine = document.getElementById('provider-reference-line');
    var paymentPanel = document.getElementById('payment-panel');
    var pollsRemaining = 40;
    var pollTimer = null;

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

    if (button) {
        button.addEventListener('click', function () {
            var phone = document.getElementById('payment-phone').value;
            button.disabled = true;
            message.textContent = 'Sending payment request...';

            fetch(paymentUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token, method: 'mpesa_stk', phone: phone })
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            throw new Error(payload.message || 'We could not start your payment request.');
                        }

                        return payload;
                    });
                })
                .then(function (payload) {
                    message.textContent = payload.message || 'Payment request sent. Please complete the payment on your phone.';
                    pollsRemaining = 40;
                    pollOrder();
                })
                .catch(function (error) {
                    message.textContent = error.message;
                    button.disabled = false;
                });
        });
    }
});
</script>
<?php endif; ?></div></section>
