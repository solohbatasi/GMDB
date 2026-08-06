<dialog id="book-purchase-dialog" class="gdmb-purchase-dialog" aria-labelledby="book-purchase-title">
    <form method="post" action="./?p=cart" class="gdmb-purchase-form">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="slug" id="book-purchase-slug" value="">

        <header class="gdmb-purchase-header">
            <div>
                <span class="gdmb-purchase-kicker">Purchase Book</span>
                <h2 id="book-purchase-title">Choose your edition</h2>
            </div>
            <button type="button" class="gdmb-purchase-close" aria-label="Close purchase dialog" title="Close">
                <i class="ion-close-round"></i>
            </button>
        </header>

        <div class="gdmb-purchase-body">
            <fieldset class="gdmb-purchase-formats">
                <legend>Book format</legend>
                <div class="gdmb-purchase-options">
                    <label class="gdmb-purchase-option" data-price-option="primary">
                        <input type="radio" name="price_option" value="primary" checked>
                        <span class="gdmb-purchase-option-copy"><strong>Hardcover</strong><small data-price-label="primary"></small></span>
                        <span class="gdmb-purchase-option-check"><i class="ion-checkmark-round" aria-hidden="true"></i></span>
                    </label>
                    <label class="gdmb-purchase-option" data-price-option="secondary">
                        <input type="radio" name="price_option" value="secondary">
                        <span class="gdmb-purchase-option-copy"><strong>Paperback</strong><small data-price-label="secondary"></small></span>
                        <span class="gdmb-purchase-option-check"><i class="ion-checkmark-round" aria-hidden="true"></i></span>
                    </label>
                </div>
            </fieldset>

            <div class="gdmb-purchase-quantity">
                <div class="gdmb-purchase-quantity-copy">
                    <strong>Quantity</strong>
                    <small>Maximum 99 copies</small>
                </div>
                <div class="gdmb-purchase-stepper">
                    <button type="button" data-quantity-action="decrease" aria-label="Decrease quantity" title="Decrease quantity"><i class="ion-minus-round"></i></button>
                    <input id="book-purchase-quantity" name="quantity" type="number" min="1" max="99" value="1" inputmode="numeric" aria-label="Book quantity">
                    <button type="button" data-quantity-action="increase" aria-label="Increase quantity" title="Increase quantity"><i class="ion-plus-round"></i></button>
                </div>
            </div>

            <div class="gdmb-purchase-summary">
                <span>Total</span>
                <strong id="book-purchase-total">KES 0</strong>
            </div>
        </div>

        <footer class="gdmb-purchase-footer">
            <button type="button" class="gdmb-purchase-button gdmb-purchase-button-secondary gdmb-purchase-cancel">Cancel</button>
            <button type="submit" class="gdmb-purchase-button gdmb-purchase-button-primary"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
        </footer>
    </form>
</dialog>

<style>
.gdmb-purchase-dialog {
    background: #fff;
    border: 0;
    border-radius: 8px;
    box-shadow: 0 28px 80px rgba(15, 23, 42, .32);
    color: #172033;
    margin: auto;
    max-height: calc(100vh - 32px);
    max-width: 520px;
    overflow: hidden;
    padding: 0;
    width: calc(100% - 32px);
}
.gdmb-purchase-dialog[open] { animation: gdmb-purchase-enter .18s ease-out; }
.gdmb-purchase-dialog::backdrop { background: rgba(15, 23, 42, .68); backdrop-filter: blur(3px); }
.gdmb-purchase-dialog, .gdmb-purchase-dialog * { box-sizing: border-box; font-family: Inter, Arial, sans-serif; letter-spacing: 0; }
.gdmb-purchase-form { margin: 0; max-height: calc(100vh - 32px); overflow-y: auto; }
.gdmb-purchase-header { align-items: flex-start; border-bottom: 1px solid #e8ebf0; display: flex; justify-content: space-between; padding: 22px 24px 18px; }
.gdmb-purchase-kicker { color: #d35400; display: block; font-size: 11px; font-weight: 800; line-height: 1; margin-bottom: 7px; text-transform: uppercase; }
.gdmb-purchase-header h2 { color: #111827; font-size: 20px; font-weight: 750; line-height: 1.3; margin: 0; overflow-wrap: anywhere; }
.gdmb-purchase-close { align-items: center; appearance: none; background: #f4f6f8; border: 0; border-radius: 50%; color: #596273; cursor: pointer; display: flex; flex: 0 0 36px; font-size: 16px; height: 36px; justify-content: center; margin-left: 18px; padding: 0; width: 36px; }
.gdmb-purchase-close:hover { background: #e9edf2; color: #111827; }
.gdmb-purchase-body { background: #f8fafc; padding: 22px 24px; }
.gdmb-purchase-formats { border: 0; margin: 0; min-width: 0; padding: 0; }
.gdmb-purchase-formats legend { border: 0; color: #4b5565; font-size: 12px; font-weight: 750; line-height: 1; margin: 0 0 10px; padding: 0; text-transform: uppercase; }
.gdmb-purchase-options { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.gdmb-purchase-option { align-items: center; background: #fff; border: 1px solid #dce1e8; border-radius: 6px; cursor: pointer; display: flex; min-height: 74px; padding: 14px; position: relative; transition: border-color .15s ease, box-shadow .15s ease; }
.gdmb-purchase-option[hidden] { display: none; }
.gdmb-purchase-option:has(input:checked) { border-color: #d35400; box-shadow: inset 0 0 0 1px #d35400; }
.gdmb-purchase-option:has(input:focus-visible) { outline: 2px solid #112243; outline-offset: 2px; }
.gdmb-purchase-option input { opacity: 0; position: absolute; }
.gdmb-purchase-option-copy { display: flex; flex: 1; flex-direction: column; min-width: 0; }
.gdmb-purchase-option-copy strong { color: #1b2434; font-size: 14px; font-weight: 750; line-height: 1.3; }
.gdmb-purchase-option-copy small { color: #657084; font-size: 12px; line-height: 1.3; margin-top: 4px; }
.gdmb-purchase-option-check { align-items: center; background: #d35400; border-radius: 50%; color: #fff; display: flex; flex: 0 0 20px; font-size: 11px; height: 20px; justify-content: center; margin-left: 10px; opacity: 0; width: 20px; }
.gdmb-purchase-option:has(input:checked) .gdmb-purchase-option-check { opacity: 1; }
.gdmb-purchase-quantity { align-items: center; border-top: 1px solid #e3e7ed; display: flex; justify-content: space-between; margin-top: 22px; padding-top: 20px; }
.gdmb-purchase-quantity-copy strong { color: #1b2434; display: block; font-size: 14px; line-height: 1.3; }
.gdmb-purchase-quantity-copy small { color: #737d8d; display: block; font-size: 12px; line-height: 1.3; margin-top: 3px; }
.gdmb-purchase-stepper { background: #fff; border: 1px solid #d8dde5; border-radius: 6px; display: grid; grid-template-columns: 40px 50px 40px; height: 42px; overflow: hidden; }
.gdmb-purchase-stepper button, .gdmb-purchase-stepper input { appearance: none; background: #fff; border: 0; border-radius: 0; color: #1b2434; font-size: 14px; margin: 0; min-width: 0; padding: 0; text-align: center; }
.gdmb-purchase-stepper button { cursor: pointer; }
.gdmb-purchase-stepper button:hover { background: #f1f4f7; }
.gdmb-purchase-stepper input { border-left: 1px solid #e3e7ed; border-right: 1px solid #e3e7ed; font-weight: 750; width: 50px; }
.gdmb-purchase-stepper input::-webkit-inner-spin-button, .gdmb-purchase-stepper input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.gdmb-purchase-summary { align-items: center; background: #112243; border-radius: 6px; display: flex; justify-content: space-between; margin-top: 20px; padding: 16px 18px; }
.gdmb-purchase-summary span { color: rgba(255,255,255,.7); font-size: 11px; font-weight: 800; text-transform: uppercase; }
.gdmb-purchase-summary strong { color: #fff; font-size: 21px; font-weight: 750; line-height: 1; }
.gdmb-purchase-footer { background: #fff; border-top: 1px solid #e8ebf0; display: flex; gap: 10px; justify-content: flex-end; padding: 16px 24px 20px; }
.gdmb-purchase-button { align-items: center; appearance: none; border-radius: 6px; cursor: pointer; display: inline-flex; font-size: 12px; font-weight: 800; gap: 7px; justify-content: center; min-height: 42px; padding: 10px 18px; text-transform: uppercase; }
.gdmb-purchase-button-secondary { background: #fff; border: 1px solid #d6dbe3; color: #323b4b; }
.gdmb-purchase-button-secondary:hover { background: #f5f7f9; border-color: #bcc3ce; }
.gdmb-purchase-button-primary { background: #d35400; border: 1px solid #d35400; color: #fff; min-width: 168px; }
.gdmb-purchase-button-primary:hover { background: #b94700; border-color: #b94700; }
@keyframes gdmb-purchase-enter { from { opacity: 0; transform: translateY(10px) scale(.985); } to { opacity: 1; transform: translateY(0) scale(1); } }
@media (max-width: 560px) {
    .gdmb-purchase-header, .gdmb-purchase-body, .gdmb-purchase-footer { padding-left: 18px; padding-right: 18px; }
    .gdmb-purchase-options { grid-template-columns: 1fr; }
    .gdmb-purchase-footer { display: grid; grid-template-columns: 1fr 1fr; }
    .gdmb-purchase-button { min-width: 0; padding-left: 10px; padding-right: 10px; }
}
</style>

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

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.book-purchase-trigger');
        if (!button) return;

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

    dialog.querySelector('.gdmb-purchase-close').addEventListener('click', closeDialog);
    dialog.querySelector('.gdmb-purchase-cancel').addEventListener('click', closeDialog);
    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) closeDialog();
    });
});
</script>
