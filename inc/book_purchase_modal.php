<dialog id="book-purchase-dialog" class="book-purchase-dialog" aria-labelledby="book-purchase-title">
    <form method="post" action="./?p=cart" class="book-purchase-form">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="slug" id="book-purchase-slug" value="">

        <div class="book-purchase-dialog-header">
            <div>
                <span class="books-eyebrow">Purchase Book</span>
                <h2 id="book-purchase-title">Choose your edition</h2>
            </div>
            <button type="button" class="book-purchase-close" aria-label="Close purchase dialog" title="Close">
                <i class="ion-close-round"></i>
            </button>
        </div>

        <div class="book-purchase-dialog-body">
            <fieldset class="book-purchase-formats">
                <legend>Book format</legend>
                <label class="book-format-option" data-price-option="primary">
                    <input type="radio" name="price_option" value="primary" checked>
                    <span><strong>Hardcover</strong><small data-price-label="primary"></small></span>
                    <i class="ion-checkmark-round" aria-hidden="true"></i>
                </label>
                <label class="book-format-option" data-price-option="secondary">
                    <input type="radio" name="price_option" value="secondary">
                    <span><strong>Paperback</strong><small data-price-label="secondary"></small></span>
                    <i class="ion-checkmark-round" aria-hidden="true"></i>
                </label>
            </fieldset>

            <div class="book-purchase-quantity-row">
                <div>
                    <strong>Quantity</strong>
                    <small>Select up to 99 copies</small>
                </div>
                <div class="book-quantity-control">
                    <button type="button" data-quantity-action="decrease" aria-label="Decrease quantity" title="Decrease quantity"><i class="ion-minus-round"></i></button>
                    <input id="book-purchase-quantity" name="quantity" type="number" min="1" max="99" value="1" inputmode="numeric" aria-label="Book quantity">
                    <button type="button" data-quantity-action="increase" aria-label="Increase quantity" title="Increase quantity"><i class="ion-plus-round"></i></button>
                </div>
            </div>

            <div class="book-purchase-total">
                <span>Total</span>
                <strong id="book-purchase-total">KES 0</strong>
            </div>
        </div>

        <div class="book-purchase-dialog-actions">
            <button type="button" class="book-btn book-btn-outline book-purchase-cancel">Cancel</button>
            <button type="submit" class="book-btn book-btn-solid"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
        </div>
    </form>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var dialog = document.getElementById('book-purchase-dialog');
    if (!dialog) return;

    var title = document.getElementById('book-purchase-title');
    var slug = document.getElementById('book-purchase-slug');
    var quantity = document.getElementById('book-purchase-quantity');
    var total = document.getElementById('book-purchase-total');
    var currentPrices = { primary: 0, secondary: 0 };
    var currency = 'KES';

    function formatMoney(amount) {
        return currency + ' ' + Number(amount).toLocaleString('en-KE', {
            minimumFractionDigits: Number(amount) % 1 === 0 ? 0 : 2,
            maximumFractionDigits: 2
        });
    }

    function selectedOption() {
        var selected = dialog.querySelector('input[name="price_option"]:checked');
        return selected ? selected.value : 'primary';
    }

    function updateTotal() {
        var count = Math.max(1, Math.min(99, parseInt(quantity.value, 10) || 1));
        quantity.value = count;
        total.textContent = formatMoney((currentPrices[selectedOption()] || 0) * count);
    }

    function closeDialog() {
        if (typeof dialog.close === 'function') dialog.close();
        else dialog.removeAttribute('open');
    }

    document.querySelectorAll('.book-purchase-trigger').forEach(function (button) {
        button.addEventListener('click', function () {
            currentPrices.primary = parseFloat(button.dataset.primaryPrice || '0');
            currentPrices.secondary = parseFloat(button.dataset.secondaryPrice || '0');
            currency = button.dataset.currency || 'KES';
            title.textContent = button.dataset.bookTitle || 'Choose your edition';
            slug.value = button.dataset.bookSlug || '';
            quantity.value = '1';

            dialog.querySelectorAll('[data-price-option]').forEach(function (option) {
                var key = option.dataset.priceOption;
                var available = currentPrices[key] > 0;
                option.hidden = !available;
                option.querySelector('[data-price-label]').textContent = available ? formatMoney(currentPrices[key]) : '';
                option.querySelector('input').checked = false;
            });

            var firstAvailable = dialog.querySelector('[data-price-option]:not([hidden]) input');
            if (firstAvailable) firstAvailable.checked = true;
            updateTotal();

            if (typeof dialog.showModal === 'function') dialog.showModal();
            else dialog.setAttribute('open', '');
        });
    });

    dialog.querySelectorAll('input[name="price_option"]').forEach(function (input) {
        input.addEventListener('change', updateTotal);
    });
    quantity.addEventListener('change', updateTotal);
    quantity.addEventListener('input', updateTotal);

    dialog.querySelectorAll('[data-quantity-action]').forEach(function (button) {
        button.addEventListener('click', function () {
            var change = button.dataset.quantityAction === 'increase' ? 1 : -1;
            quantity.value = Math.max(1, Math.min(99, (parseInt(quantity.value, 10) || 1) + change));
            updateTotal();
        });
    });

    dialog.querySelector('.book-purchase-close').addEventListener('click', closeDialog);
    dialog.querySelector('.book-purchase-cancel').addEventListener('click', closeDialog);
    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) closeDialog();
    });
});
</script>
