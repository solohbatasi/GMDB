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
?>
<section class="books-page"><div class="container"><?php include_once 'inc/breadcrumbs.php'; ?>
<div class="books-page-header">
    <div>
        <span class="books-eyebrow">Checkout</span>
        <h1>Guest Checkout</h1>
        <p>Select a pickup point for your books. Payment is completed after the order is created.</p>
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
        <form method="post" class="checkout-form">
            <h3>Customer</h3>
            <input name="name" class="form-control" placeholder="Full name" required><br>
            <input name="email" type="email" class="form-control" placeholder="Email" required><br>
            <input name="phone" class="form-control" placeholder="Phone e.g. 0712345678" required><br>

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

            <textarea name="customer_note" class="form-control" placeholder="Optional note"></textarea><br>
            <button class="book-btn book-btn-solid" <?php echo empty($quote['valid']) ? 'disabled' : ''; ?>>Create Order</button>
        </form>
    </div>
    <div class="col-md-5">
        <h3>Order Summary</h3>
        <?php foreach ($quote['items'] as $item): ?>
            <p><?php echo gdmb_e($item['title'] ?? $item['slug']); ?> x <?php echo (int) $item['quantity']; ?> <strong><?php echo gdmb_e(gdmb_format_price($item['line_total'] ?? null, $quote['currency'])); ?></strong></p>
        <?php endforeach; ?>
        <hr>
        <p>Subtotal: <?php echo gdmb_e(gdmb_format_price($quote['subtotal'], $quote['currency'])); ?></p>
        <p>Pickup: KES 0</p>
        <h3>Total: <?php echo gdmb_e(gdmb_format_price($quote['total'], $quote['currency'])); ?></h3>
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
});
</script>
<?php endif; ?></div></section>
