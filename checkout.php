<?php
require_once 'inc/cart.php';

$quote = $GLOBALS['gdmb_checkout_quote'] ?? gdmb_cart_quote();
$error = $GLOBALS['gdmb_checkout_error'] ?? null;
if (! $error && ! $quote && gdmb_cart_count() > 0) {
    $error = 'Live checkout pricing is temporarily unavailable. Please try again shortly.';
}

$pickupResponse = gdmb_store_api_get('pickup-locations');
$pickupLocations = is_array($pickupResponse['data'] ?? null) ? $pickupResponse['data'] : [];
$selectedPickupId = (int) ($_POST['pickup_location_id'] ?? ($pickupLocations[0]['id'] ?? 0));
gdmb_session_start();
if (! isset($_SESSION['gdmb_checkout_token'])) {
    $_SESSION['gdmb_checkout_token'] = bin2hex(random_bytes(24));
}
$checkoutToken = $_SESSION['gdmb_checkout_token'];
?>
<section class="books-page"><div class="container"><?php include_once 'inc/breadcrumbs.php'; ?>
<div class="books-page-header">
    <div>
        <span class="books-eyebrow">Checkout</span>
        <h1>Guest Checkout</h1>
        <p>Enter your details and M-Pesa number. One click reserves your books and sends the payment prompt.</p>
    </div>
</div>

<?php if ($error): ?><p style="color:#b00020;"><?php echo gdmb_e($error); ?></p><?php endif; ?>

<?php if (! $quote || empty($quote['items'])): ?>
    <p>Your cart is empty.</p>
<?php elseif (empty($pickupLocations)): ?>
    <p style="color:#b00020;">No pickup location is currently available. Please contact us before checking out.</p>
<?php else: ?>
<div class="row">
    <div class="col-md-7">
        <form id="checkout-form" method="post" class="checkout-form">
            <h3>Customer</h3>
            <input name="name" class="form-control" placeholder="Full name" required><br>
            <input name="email" type="email" class="form-control" placeholder="Email" required><br>
            <input name="phone" class="form-control" placeholder="Phone e.g. 0712345678" required><br>

            <h3>M-Pesa Payment</h3>
            <input name="mpesa_phone" class="form-control" inputmode="tel" autocomplete="tel" placeholder="M-Pesa number e.g. 0712345678" required><br>

            <h3>Pickup Point</h3>
            <input type="hidden" name="delivery_method" value="pickup">
            <select id="pickup-location-select" name="pickup_location_id" class="form-control" required>
                <option value="">Select pickup location</option>
                <?php foreach ($pickupLocations as $location): ?>
                    <?php $mapUrl = gdmb_pickup_map_url($location); ?>
                    <option
                        value="<?php echo (int) $location['id']; ?>"
                        data-name="<?php echo gdmb_e($location['name'] ?? ''); ?>"
                        data-address="<?php echo gdmb_e($location['address'] ?? ''); ?>"
                        data-city="<?php echo gdmb_e($location['city'] ?? ''); ?>"
                        data-county="<?php echo gdmb_e($location['county'] ?? ''); ?>"
                        data-instructions="<?php echo gdmb_e($location['instructions'] ?? ''); ?>"
                        data-map-url="<?php echo gdmb_e($mapUrl); ?>"
                        <?php echo (int) $location['id'] === $selectedPickupId ? 'selected' : ''; ?>
                    >
                        <?php echo gdmb_e($location['name'] . ' - ' . $location['address']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="pickup-location-details" class="book-card" style="padding:16px; margin:16px 0; display:none;">
                <h4 id="pickup-detail-name" style="margin-top:0;"></h4>
                <p id="pickup-detail-address"></p>
                <p id="pickup-detail-instructions"></p>
                <a id="pickup-detail-map" class="book-btn" href="#" target="_blank" rel="noopener">View Map</a>
            </div>

            <textarea name="customer_note" class="form-control" placeholder="Optional note"></textarea>
        </form>
    </div>
    <div class="col-md-5">
        <h3>Order Summary</h3>
        <?php foreach ($quote['items'] as $item): ?>
            <p><?php echo gdmb_e($item['title'] ?? $item['slug']); ?> (<?php echo gdmb_e($item['price_option_label'] ?? 'Hardcover'); ?>) x <?php echo (int) $item['quantity']; ?> <strong><?php echo gdmb_e(gdmb_format_price($item['line_total'] ?? null, $quote['currency'])); ?></strong></p>
        <?php endforeach; ?>
        <hr>
        <p>Subtotal: <?php echo gdmb_e(gdmb_format_price($quote['subtotal'], $quote['currency'])); ?></p>
        <p>Pickup: KES 0</p>
        <h3>Total: <?php echo gdmb_e(gdmb_format_price($quote['total'], $quote['currency'])); ?></h3>
        <button id="checkout-submit" form="checkout-form" class="book-btn book-btn-solid" style="width:100%; margin-top:12px;" <?php echo empty($quote['valid']) ? 'disabled' : ''; ?>>Pay with M-Pesa</button>
        <p id="checkout-payment-message" role="status" aria-live="polite" style="margin-top:12px;"></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('pickup-location-select');
    var panel = document.getElementById('pickup-location-details');
    var name = document.getElementById('pickup-detail-name');
    var address = document.getElementById('pickup-detail-address');
    var instructions = document.getElementById('pickup-detail-instructions');
    var map = document.getElementById('pickup-detail-map');
    var form = document.getElementById('checkout-form');
    var submit = document.getElementById('checkout-submit');
    var paymentMessage = document.getElementById('checkout-payment-message');
    var checkoutUrl = <?php echo json_encode(gdmb_store_api_base_url().'/checkout'); ?>;
    var checkoutToken = <?php echo json_encode($checkoutToken); ?>;
    var cartItems = <?php echo json_encode(gdmb_cart_items(), JSON_UNESCAPED_SLASHES); ?>;
    var pollsRemaining = 100;

    function renderPickupDetails() {
        var option = select.options[select.selectedIndex];
        if (!option || !option.value) {
            panel.style.display = 'none';
            return;
        }

        name.textContent = option.dataset.name || 'Pickup point';
        address.textContent = [option.dataset.address, option.dataset.city, option.dataset.county].filter(Boolean).join(', ');
        instructions.textContent = option.dataset.instructions || 'Pickup details will be confirmed after payment.';
        map.href = option.dataset.mapUrl || '#';
        panel.style.display = '';
    }

    if (select) {
        select.addEventListener('change', renderPickupDetails);
        renderPickupDetails();
    }

    function completeLocalCheckout(orderNumber, token, redirectUrl) {
        var body = new URLSearchParams({
            action: 'complete_checkout',
            order: orderNumber,
            token: token
        });

        fetch('./?p=checkout', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).finally(function () {
            window.location.href = redirectUrl;
        });
    }

    function pollPayment(statusUrl, order) {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                var status = payload && payload.data;
                if (!status) {
                    throw new Error('Payment status is temporarily unavailable.');
                }

                paymentMessage.textContent = status.message;
                if (status.status === 'successful') {
                    completeLocalCheckout(status.order_number, order.public_token, status.redirect_url);
                    return;
                }

                if (status.status === 'failed') {
                    submit.disabled = !status.can_retry;
                    submit.textContent = status.can_retry ? 'Retry M-Pesa Payment' : 'Payment unavailable';
                    return;
                }

                if (--pollsRemaining > 0) {
                    window.setTimeout(function () { pollPayment(statusUrl, order); }, 3000);
                } else {
                    submit.disabled = false;
                    paymentMessage.textContent = 'Payment is still being checked. You can safely refresh this page.';
                }
            })
            .catch(function () {
                if (--pollsRemaining > 0) {
                    window.setTimeout(function () { pollPayment(statusUrl, order); }, 5000);
                } else {
                    submit.disabled = false;
                }
            });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!form.reportValidity()) {
                return;
            }

            var fields = new FormData(form);
            submit.disabled = true;
            submit.textContent = 'Sending M-Pesa prompt...';
            paymentMessage.textContent = 'Creating your order and reserving your books...';
            pollsRemaining = 100;

            fetch(checkoutUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    checkout_token: checkoutToken,
                    items: cartItems,
                    customer: {
                        name: fields.get('name'),
                        email: fields.get('email'),
                        phone: fields.get('phone')
                    },
                    payment: { phone: fields.get('mpesa_phone') },
                    fulfillment: {
                        method: 'pickup',
                        address: null,
                        city: null,
                        county: null,
                        pickup_location_id: Number(fields.get('pickup_location_id'))
                    },
                    customer_note: fields.get('customer_note') || null
                })
            }).then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok && response.status !== 202) {
                        throw new Error(payload.message || 'M-Pesa could not be started. Please try again.');
                    }
                    return payload;
                });
            }).then(function (payload) {
                if (!payload.data || !payload.payment || !payload.payment.status_url) {
                    throw new Error(payload.message || 'The payment request could not be confirmed.');
                }
                paymentMessage.textContent = payload.payment.message;
                submit.textContent = 'Waiting for M-Pesa...';
                pollPayment(payload.payment.status_url, payload.data);
            }).catch(function (error) {
                paymentMessage.textContent = error.message;
                submit.disabled = false;
                submit.textContent = 'Retry M-Pesa Payment';
            });
        });
    }
});
</script>
<?php endif; ?></div></section>
