<?php
require_once 'inc/cart.php';

$quote = $GLOBALS['gdmb_checkout_quote'] ?? gdmb_cart_quote();
$error = $GLOBALS['gdmb_checkout_error'] ?? null;
if (! $error && ! $quote && gdmb_cart_count() > 0) {
    $error = 'Live checkout pricing is temporarily unavailable. Please try again shortly.';
}

$pickupResponse = gdmb_store_api_get('pickup-locations');
$pickupLocations = is_array($pickupResponse['data'] ?? null) ? $pickupResponse['data'] : [];
?>
<section class="books-page"><div class="container"><?php include_once 'inc/breadcrumbs.php'; ?><div class="books-page-header"><div><span class="books-eyebrow">Checkout</span><h1>Guest Checkout</h1><p>Your order will be created as awaiting payment. Payment is not collected yet.</p></div></div>
<?php if ($error): ?><p style="color:#b00020;"><?php echo gdmb_e($error); ?></p><?php endif; ?>
<?php if (! $quote || empty($quote['items'])): ?><p>Your cart is empty.</p><?php else: ?>
<div class="row"><div class="col-md-7"><form method="post" class="checkout-form"><h3>Customer</h3><input name="name" class="form-control" placeholder="Full name" required><br><input name="email" type="email" class="form-control" placeholder="Email" required><br><input name="phone" class="form-control" placeholder="Phone e.g. 0712345678" required><br><h3>Fulfillment</h3><label><input type="radio" name="delivery_method" value="delivery" checked> Delivery</label> <label><input type="radio" name="delivery_method" value="pickup"> Pickup</label><br><br><input name="address" class="form-control" placeholder="Delivery address"><br><input name="city" class="form-control" placeholder="Town / City"><br><input name="county" class="form-control" placeholder="County"><br><select name="pickup_location_id" class="form-control"><option value="">Select pickup location</option><?php foreach ($pickupLocations as $location): ?><option value="<?php echo (int) $location['id']; ?>"><?php echo gdmb_e($location['name'] . ' - ' . $location['address']); ?></option><?php endforeach; ?></select><br><textarea name="customer_note" class="form-control" placeholder="Optional note"></textarea><br><button class="book-btn book-btn-solid" <?php echo empty($quote['valid']) ? 'disabled' : ''; ?>>Create Order</button></form></div><div class="col-md-5"><h3>Order Summary</h3><?php foreach ($quote['items'] as $item): ?><p><?php echo gdmb_e($item['title'] ?? $item['slug']); ?> × <?php echo (int) $item['quantity']; ?> <strong><?php echo gdmb_e(gdmb_format_price($item['line_total'] ?? null, $quote['currency'])); ?></strong></p><?php endforeach; ?><hr><p>Subtotal: <?php echo gdmb_e(gdmb_format_price($quote['subtotal'], $quote['currency'])); ?></p><p>Delivery: <?php echo gdmb_e(gdmb_format_price($quote['shipping_total'], $quote['currency']) ?: 'KES 0'); ?></p><h3>Total: <?php echo gdmb_e(gdmb_format_price($quote['total'], $quote['currency'])); ?></h3></div></div>
<?php endif; ?></div></section>
